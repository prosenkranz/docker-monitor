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

	public function getContainerInfo($id)
	{
		$output = self::runCommand("docker inspect ".$id);
		if ($output === false)
			return null;

		$infos = json_decode($output, true);
		if (is_array($infos) && !empty($infos))
			return $infos[0];
		else
			return null;
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

	public function getContainerStatistics($id)
	{
		$timestamp = time();
		$output = self::runCommand("docker stats --no-stream ".$id);
		if ($output === false)
			return null;

		$lines = explode("\n", $output);
		if (count($lines) <= 1)
			return null;

		$statistics = parseContainerStatisticsLine($lines[1]); // 0 is table head
		if ($statistics !== null)
			$statistics->Timestamp = $timestamp;

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

	public function getContainer($id)
	{
		$container = $this->adapter->getContainerInfo($id);
		if ($container == null)
			return null;

		$container->Statistics = $this->adapter->getContainerStatistics($id);
		if ($container->Statistics)
		{
			// We already have the Id in the container info
			unset($container->Statistics->Id);
		}

		return $container;
	}

	public function getContainers()
	{
		$containers = [];
		$containerIds = $this->adapter->enumerateContainers();
		foreach ($containerIds as $id)
			$containers[] = $this->getContainer($id);

		return $containers;
	}
}
