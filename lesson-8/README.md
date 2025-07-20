# ДЗ: Распределенные транзакции

## Окружение
#### DockerHub
- https://hub.docker.com/repository/docker/confuciy/otus-lesson-8-saga/general
- Приложение собрано из нескольких сервисов, каждый из своего образа.
```
Образы:
docker pull confuciy/otus-lesson-8-saga:php-app
docker pull confuciy/otus-lesson-8-saga:service-user
docker pull confuciy/otus-lesson-8-saga:service-auth
docker pull confuciy/otus-lesson-8-saga:service-notification
docker pull confuciy/otus-lesson-8-saga:service-order
docker pull confuciy/otus-lesson-8-saga:service-billing
docker pull confuciy/otus-lesson-8-saga:service-warehouse
docker pull confuciy/otus-lesson-8-saga:service-delivery
```
Для удобства все образы создаются из одного Dockerfile с разбивкой на target.
```
Например:
docker build -t confuciy/otus-lesson-8-saga:service-user --target=service-user .
```

![pods_1.png](./img/pods_1.png)

#### GitHub
- https://github.com/confuciy/microservice_architecture/tree/main/lesson-8
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

![interaction_of_services_2.png](./img/interaction_of_services.png)

#### Схема взаимодействия сервисов на примере успешного создания заказа

![interaction_of_services.png](./img/interaction_of_services_ok.png)

#### Схема взаимодействия сервисов на примере создания заказа c ошибкой на этапе бронирования товара на складе

![interaction_of_services.png](./img/interaction_of_services_error.png)

### Swagger (OpenAPI)

Методы сервисов описаны в документации - http://arch.homework/api/documentation/

![swagger_openapi_1.png](./img/swagger_openapi_1.png)

### Тесты Postman
#### Коллекция Postman
- postman-collection.json

```
newman run postman_collection.json --delay-request 100
```

![newman_1.png](./img/newman_1.png)

###Сценарий:

- вход пользователя;
![postman_1.png](./img/postman_1.png)

- просмотр баланса биллинг-аккаунта;
![postman_2.png](./img/postman_2.png)

- создание заказа с 2 товарами;
![postman_3.png](./img/postman_3.png)
![postman_3.txt](./img/postman_3.txt)

- просмотр баланса биллинг-аккаунта;
![postman_4.png](./img/postman_4.png)

- просмотр созданного заказа;
![postman_5.png](./img/postman_5.png)
![postman_5.txt](./img/postman_5.txt)

- просмотр списка товаров созданного заказа;
![postman_6.png](./img/postman_6.png)
![postman_6.txt](./img/postman_6.txt)

- просмотр зарезервированного курьера созданного заказа;
![postman_7.png](./img/postman_7.png)

- просмотр оповещений;
![postman_8.png](./img/postman_8.png)
![postman_8.txt](./img/postman_8.txt)

- создаем еще один заказ, с 1 товаром, которого уже нет на складе (зарезервирован первым);
- у нас проходит оплата, далее мы пытаемся забронировать товар, понимаем, что его нет;
- компенсационная транзакция возвращает деньги на счет;
- заказ отменяется;
![postman_9.png](./img/postman_9.png)

- просмотр баланса биллинг-аккаунта (средства вернулись);
![postman_10.png](./img/postman_10.png)

- просмотр оповещений;
![postman_11.png](./img/postman_11.png)
![postman_11.txt](./img/postman_11.txt)

- доставляем первый заказ;
![postman_12.png](./img/postman_12.png)
![postman_12.txt](./img/postman_12.txt)

- просмотр оповещений;
![postman_13.png](./img/postman_13.png)
![postman_13.txt](./img/postman_13.txt)