<?php
namespace App\Service\Billing\Model;

use PDO;
use App\Database\Database;

class Billing
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection('service_billing');
    }

    # Создание биллнг-аккаунта
    public function create(array $data): array
    {
        try {

            if (!isset($data['user_id']) or empty($data['user_id'])) {
                throw new \Exception('ID пользователя пустой');
            }

            $query = 'INSERT INTO billing (user_id, amount) 
              VALUES (:user_id, :amount)';
            $statement = $this->pdo->prepare($query);

            $statement->execute([
                ':user_id' => $data['user_id'],
                ':amount' => $data['amount']
            ]);

            $id = $this->pdo->lastInsertId();

            $data['billing_id'] = $id;

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    public function get(int $userId): array
    {
        try {

            if (empty($userId)) {
                throw new \Exception('ID пользователя пустой');
            }

            $query = 'SELECT * 
              FROM billing 
              WHERE user_id = :user_id 
              ORDER BY date_insert DESC';
            $statement = $this->pdo->prepare($query);
            $statement->execute([':user_id' => $userId]);
            $billing = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$billing) {
                throw new \Exception("Аккаунт не найден");
            }

            return $billing;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Изменение суммы биллинг-аккаунта
    public function amount(array $data): array
    {
        try {

            if (!isset($data['user_id']) or empty($data['user_id'])) {
                throw new \Exception('ID пользователя пустой');
            }

            $billing = $this->get($data['user_id']);

            # Рассчитываем новую сумму биллниг-аккаунта
            $new_amount = ($data['action'] == 'plus'?($billing['amount'] + $data['amount']):($billing['amount'] - $data['amount']));

            $query = 'UPDATE billing SET amount = :amount WHERE user_id = :user_id';
            $statement = $this->pdo->prepare($query);
            $statement->execute([
                ':user_id' => $data['user_id'],
                ':amount' => $new_amount
            ]);

            # Данные для сохранения действия по сумме биллниг-аккаунта
            $billing_action_data = [
                'billing_id' => $billing['billing_id'],
                'action' => $data['action'],
                'amount' => $data['amount']
            ];

            $billing['billing_action'] = $this->createAction($billing_action_data);

            return $billing;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Проверка существования пользователя по почте
    public function checkBillingExists(int $userId): bool
    {
        $query = 'SELECT billing_id 
          FROM billing 
          WHERE user_id = :user_id';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            ':user_id' => $userId
        ]);
        $billing = $statement->fetch(PDO::FETCH_ASSOC);

        if (isset($billing['billing_id']) and !empty($billing['billing_id'])) {

            return true;

        } else {

            return false;
        }
    }

    # Создание биллнг-аккаунта
    public function createAction(array $data): array
    {
        try {

            if (!isset($data['billing_id']) or empty($data['billing_id'])) {
                throw new \Exception('ID заказа пустой');
            }

            $query = 'INSERT INTO billing_actions (billing_id, action, amount) 
              VALUES (:billing_id, :action, :amount)';
            $statement = $this->pdo->prepare($query);

            $statement->execute([
                ':billing_id' => $data['billing_id'],
                ':action' => $data['action'],
                ':amount' => $data['amount']
            ]);

            $id = $this->pdo->lastInsertId();

            $data['billing_action_id'] = $id;

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }


//    public function delete(int $id): array
//    {
//        $query = 'DELETE FROM users WHERE id = :id';
//        $statement = $this->pdo->prepare($query);
//        if (!$statement->execute([':id' => $id]))
//        {
//            throw new \Exception("Error while deleting");
//        }
//
//        return ['id' => $id];
//    }
//
//    public function update(int $userId, array $data): array
//    {
//        $query = 'UPDATE users SET username = :username, first_name = :first_name, last_name = :last_name, email = :email, phone = :phone WHERE id = :id';
//        $statement = $this->pdo->prepare($query);
//        $statement->execute([
//            ':id' => $userId,
//            ':username' => $data['username'],
//            ':first_name' => $data['firstName'],
//            ':last_name' => $data['lastName'],
//            ':email' => $data['email'],
//            ':phone' => $data['phone']
//        ]);
//
//        return $data;
//    }
}