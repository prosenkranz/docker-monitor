<?php

$useMockData = false;

putenv("DOCKER_TLS_VERIFY=1");
putenv("DOCKER_HOST=tcp://10.0.0.10:2376");
putenv("DOCKER_CERT_PATH=" . __DIR__."/host-certificates");
putenv("DOCKER_MACHINE_NAME=docker-host");
