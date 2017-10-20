<?php

class ParserUtils
{
	public static function parsePercentage($str)
	{
		return floatval(str_replace('%', '', trim($str)));
	}
}

class DockerAdapter
{
	protected static function runCommand($cmd)
	{
		exec($cmd . ' 2>&1', $outputLines, $returnVal);
		$output = implode("\n", $outputLines);
		if ($returnVal === 1)
		{
			echo "[DockerAdapter] Failed run command '$cmd': " . $output;
			return false;
		}

		return $output;
	}

	/**
	 * Returns array of all container ids existing on the docker host
	 */
	public function enumerateContainers()
	{
		$output = self::runCommand("docker ps -aq");
		if ($output === false)
			return [];

		return explode("\n", $output);
	}

	public function getContainerInfos($ids)
	{
		if ($ids === null)
			return null;

		if (!is_array($ids))
			$ids = [$ids];

		$output = self::runCommand("docker inspect " . implode(' ', $ids));
		if ($output === false)
			return null;

		$infos = json_decode($output);
		if (is_array($infos) && !empty($infos))
			return $infos;
		else
			return null;
	}

	public function getContainerInfo($id)
	{
		$infos = $this->getContainerInfos([$id]);
		if ($infos === null || count($infos) == 0)
			return null;

		return $infos[0];
	}

	protected function parseContainerStatisticsLine($outputLine)
	{
		$line = preg_replace('/\s{2,}/', "\t", $outputLine);
		$cols = explode("\t", $line);
		return (object)[
			'Id' => $cols[0],
			'CPUUsagePerc' => ParserUtils::parsePercentage($cols[1]),
			'MemUsagePerc' => ParserUtils::parsePercentage($cols[3])
		];
	}

	public function getContainerStatistics($ids)
	{
		if ($ids === null)
			return null;

		if (!is_array($ids))
			$ids = [$ids];

		$timestamp = time();
		$output = self::runCommand("docker stats --no-stream " . implode(' ', $ids));
		if ($output === false)
			return null;

		$lines = explode("\n", $output);
		if (count($lines) <= 1)
			return null;

		$statistics = [];
		for ($i = 1; $i < count($lines); ++$i)
		{
			$stat = $this->parseContainerStatisticsLine($lines[$i]); // 0 is table head
			if ($stat !== null)
			{
				$stat->Timestamp = $timestamp;
				$statistics[] = $stat;
			}
		}

		return $statistics;
	}
}

class Docker
{
	protected $adapter;

	public function __construct(DockerAdapter $adapter = null)
	{
		if ($adapter !== null)
			$this->adapter = $adapter;
		else
			$this->adapter = new DockerAdapter();
	}

	public function getContainers($containerIds=null)
	{
		if ($containerIds === null)
			$containerIds = $this->adapter->enumerateContainers();

		if (!is_array($containerIds))
			$containerIds = [$containerIds];

		$containers = $this->adapter->getContainerInfos($containerIds);
		if ($containers === null)
			return [];

		$statistics = $this->adapter->getContainerStatistics($containerIds);
		foreach ($statistics as $stat)
		{
			foreach ($containers as $container)
			{
				if ($stat->Id === $container->Id)
				{
					$container->Statistics = $stat;
					unset($container->Statistics->Id); // already have that
					break;
				}
			}
		}

		return $containers;
	}

	/**
	 * If you want to get multiple containers at once, use more efficient getContainers() instead
	 */
	public function getContainer($id)
	{
		return $this->getContainers([$id]);
	}
}
