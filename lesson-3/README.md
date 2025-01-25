# ДЗ: Основы работы с Kubernetes (Часть 2)
##### DockerHub
- https://hub.docker.com/repository/docker/confuciy/otus-lesson-3-health/general

##### GitHub
- https://github.com/confuciy/microservice_architecture/tree/main/lesson-3
- `git clone https://github.com/confuciy/microservice_architecture.git <your_folder>`
##### Манифесты Deployment, Service, Ingress
- k8s-manifests/

##### Настройки hosts
Прописываем в C:\Windows\System32\drivers\etc\hosts
- 127.0.0.1 arch.homework

##### Коллекция Postman
- postman-collection-lesson-3.json

- base_url = http://arch.homework:8000

--

##### Запуск приложения
Применяем маифесты
- `kubectl apply -f k8s-manifests/`

Останавливаем манифесты
- `kubectl delete -f k8s-manifests/`

Запускаем перенаправление
- `kubectl port-forward service/health-check-service 8000:80`

--

##### Смотрим работу в браузере

Адрес: http://localhost:8000/health
- Ответ: `{
    "status": "ok"
}`

Адрес: http://arch.homework:8000/health
- Ответ: `{
    "status": "ok"
}`

Адрес: http://arch.homework:8000/otusapp/student_name/health
- Ответ: `{
    "status": "ok"
}`