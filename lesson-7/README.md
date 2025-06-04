# ДЗ: Stream processing

## Окружение
#### DockerHub
- https://hub.docker.com/repository/docker/confuciy/otus-lesson-7-stream-processing/general

Приложение собрано из нескольких сервисов, каждый из своего образа.
```
Образы:
docker pull confuciy/otus-lesson-7-stream-processing:php-app
docker pull confuciy/otus-lesson-7-stream-processing:service-user
docker pull confuciy/otus-lesson-7-stream-processing:service-auth
docker pull confuciy/otus-lesson-7-stream-processing:service-notification
docker pull confuciy/otus-lesson-7-stream-processing:service-order
docker pull confuciy/otus-lesson-7-stream-processing:service-billing
```
Для удобства все образы создаются из одного Dockerfile с разбивкой на target.
```
Например:
docker build -t confuciy/otus-lesson-7-stream-processing:service-user --target=service-user .
```

![pods_1.png](./img/pods_1.png)

#### GitHub
- https://github.com/confuciy/microservice_architecture/tree/main/lesson-7
- `git clone https://github.com/confuciy/microservice_architecture.git <your_folder>`

#### Настройки
Прописываем в C:\Windows\System32\drivers\etc\hosts:
```
185.236.28.169 arch.homework
```
##### Как работает:
```
IP белый, так что приложение можно попробовать через интернет (работает не всегда, иногда неттоп перегревается и виснет). 
Роутер перенаправляет внешние запросы на неттоп в домашней сети.
На неттопе поднят Nginx, который проксирует внешние запросы на Minikube. 
В Minikube происходит вся магия, ответ возвращается пользователю.
```

##### Запуск и остановка приложения:
```
Запуск, namespace default:
helm install php-app ./helm
```
```
Остановка:
helm uninstall php-app
```

## Приложение

#### Описание архитектурного решения и схема взаимодействия сервисов

![interaction_of_services_2.png](./img/interaction_of_services_2.png)

##### Схема взаимодействия сервисов на примере создания заказа

![interaction_of_services.png](./img/interaction_of_services.png)

#### Swagger (OpenAPI)

Методы сервисов описаны в документации - http://arch.homework/api/documentation/

![swagger_openapi_1.png](./img/swagger_openapi_1.png)

#### Тесты Postman
#### Коллекция Postman
- postman-collection.json
- base_url = http://arch.homework
- id = 0
- email_iterator = 0

```
newman run postman_collection.json
```

![newman_1.png](./img/newman_1.png)

Сценарий:
- создание пользователя, также создается биллинг-аккаунт;

![postman_1.png](./img/postman_1.png)

- вход пользователя;

![postman_2.png](./img/postman_2.png)

- просмотр баланса биллинг-аккаунта;

![postman_3.png](./img/postman_3.png)

- пополнение баланса биллинг-аккаунта;

![postman_4.png](./img/postman_4.png)

- просмотр баланса биллинг-аккаунта после пополнения;

![postman_5.png](./img/postman_5.png)

- создаем заказ, на который хватает средств;

![postman_6.png](./img/postman_6.png)

- просмотр баланса биллинг-аккаунта;

![postman_7.png](./img/postman_7.png)

- просмотр оповещений;

![postman_8.png](./img/postman_8.png)

- создаем заказ, на который не хватает средств;

![postman_9.png](./img/postman_9.png)

- просмотр баланса биллинг-аккаунта;

![postman_10.png](./img/postman_10.png)

- просмотр оповещений;

![postman_11.png](./img/postman_11.png)
