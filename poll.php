<?php

require_once "Docker.php";

// ------------------------------------------------------------------------------------------------

$useMockData = false;
@include "config.local.php";

// ------------------------------------------------------------------------------------------------

class MockDockerAdapter extends DockerAdapter
{
	public function enumerateContainers()
	{
		return [
			'91fae44e0334', '0451123a5c33', '21a1ab78f3da', '30d753d798f5', '413c21b87b81',
			'f1829e56e69e', 'd14117b85702', 'ff23ace5a548', 'f7c09191bc2a', '2ea4d3f6a1d7'
		];
	}

	public function getContainerInfo($id)
	{
		return (object)[
			'Id' => $id,
			'Name' => 'mock',
		];
	}

	public function getContainerStatistics($id)
	{
		$timestamp = time();
		$output = "CONTAINER           CPU %               MEM USAGE / LIMIT     MEM %               NET I/O             BLOCK I/O           PIDS
91fae44e0334        0.11%               1.004GiB / 19.57GiB   5.13%               128MB / 14.7MB      7.49MB / 106MB      106
0451123a5c33        0.12%               1.004GiB / 19.57GiB   5.13%               129MB / 15.1MB      6.93MB / 106MB      104
21a1ab78f3da        0.14%               1004MiB / 19.57GiB    5.01%               129MB / 15.1MB      6.57MB / 106MB      105
30d753d798f5        0.11%               751MiB / 19.57GiB     3.75%               112MB / 5.67MB      8.92MB / 106MB      96
413c21b87b81        0.23%               1.514GiB / 19.57GiB   7.74%               303MB / 126MB       18.6MB / 213MB      214
f1829e56e69e        0.11%               819.7MiB / 19.57GiB   4.09%               112MB / 5.52MB      7.04MB / 106MB      94
d14117b85702        0.16%               854.3MiB / 19.57GiB   4.26%               112MB / 5.61MB      8.38MB / 106MB      100
ff23ace5a548        0.17%               836.7MiB / 19.57GiB   4.18%               112MB / 5.71MB      8.83MB / 106MB      94
f7c09191bc2a        0.17%               980.1MiB / 19.57GiB   4.89%               129MB / 14.9MB      7.44MB / 106MB      104
2ea4d3f6a1d7        0.13%               1.007GiB / 19.57GiB   5.15%               129MB / 15.1MB      52.2MB / 107MB      104";

		$lines = explode("\n", $output);
		for ($i = 1; $i < count($lines); ++$i)
		{
			$stats = $this->parseContainerStatisticsLine($lines[$i]);
			if ($stats->Id === $id)
			{
				$stats->Timestamp = $timestamp;
				return $stats;
			}
		}

		return null;
	}
}

// ------------------------------------------------------------------------------------------------

$docker = new Docker($useMockData ? new MockDockerAdapter() : null);

header("Content-Type: application/json");
echo json_encode($docker->getContainers(), JSON_PRETTY_PRINT);
