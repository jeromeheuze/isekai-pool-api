# Merge these into `config.json` (after `cp config_example.json config.json`)

Do **not** commit the resulting `config.json`. Set a strong random `website.adminCenter.password`.

## Suggested overrides

```json
{
  "logLevel": "warning",
  "logColors": false,
  "clustering": {
    "enabled": true,
    "forks": 2
  },
  "defaultPoolConfigs": {
    "blockRefreshInterval": 1000,
    "jobRebroadcastTimeout": 55,
    "connectionTimeout": 600,
    "validateWorkerUsername": true,
    "redis": {
      "host": "127.0.0.1",
      "port": 6379
    }
  },
  "website": {
    "enabled": true,
    "host": "127.0.0.1",
    "port": 8080,
    "stratumHost": "koto.isekai-pool.com",
    "stats": {
      "updateInterval": 15,
      "historicalRetention": 43200,
      "hashrateWindow": 300
    },
    "adminCenter": {
      "enabled": true,
      "password": "CHANGE_THIS_ADMIN_PASSWORD"
    }
  },
  "redis": {
    "host": "127.0.0.1",
    "port": 6379
  }
}
```

Deep-merge with upstream: keep any keys `config_example.json` already defines (pools list, etc.) and only overwrite / add the sections above.
