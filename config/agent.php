<?php
return [
  'enabled' => env('AGENT_ENABLED', true),
  'endpoint' => env('AGENT_ENDPOINT', null),
  'license_key' => env('AGENT_LICENSE_KEY', null),
  'heartbeat_interval_minutes' => 10,
  'batch_size' => 50,
  'exclude_fields' => ['password','_token'],
];
