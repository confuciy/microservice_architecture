# ДЗ: Prometheus. Grafana

## Окружение
#### DockerHub
- https://hub.docker.com/r/confuciy/otus-lesson-5-user-service

#### GitHub
- https://github.com/confuciy/microservice_architecture/tree/main/lesson-5
- `git clone https://github.com/confuciy/microservice_architecture.git <your_folder>`

#### Коллекция Postman
- postman-collection.json
- base_url = http://arch.homework
- id = 1

## Метрики для Prometheus:
```
Настроен сбор метрик через сервисы с указанием таргетов Prometheus
helm upgrade prometheus prometheus-community/prometheus -f ./monitoring/prometheus-values.yaml --set-file extraScrapeConfigs=./monitoring/prometheus-metrics.yaml
```
Метрики собираются с:
- Nginx Ingress Controller;
- Nginx Exporter;
- API metrics (добавленные в приложение метрики);
- Postgres Exporter.
![prometheus_1.png](./img/prometheus_1.png)