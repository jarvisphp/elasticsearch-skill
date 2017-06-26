# Elasticsearch skill

To add in `app_settings.json`:

```json
{
    "providers": [
        "Jarvis\\Skill\\Elasticsearch\\ElasticsearchCore",
    ],
    "extra": {
        "elasticsearch": {
            "index": {
                "settings": {
                    "number_of_shards": 5,
                    "number_of_replicas": 0
                }
            },
            "model_type_mappings": {
                "__MODEL_NAMESPACE__": {
                    "index_name": "__INDEX_NAME__",
                    "store_class": "__MODEL_STORE_NAMESPACE__"
                }
            }
        }
    }
}
```
