<?php

namespace App\Command;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use ParseCsv\Csv;
use PDO;
use PDOException;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class UploadUser extends CLI
{

    protected $logdefault = 'error';

// register options and arguments
    protected function setup(Options $options): void
    {
        $options->setHelp('This is a script which accepts a CSV file as input and processes the CSV file. The parsed file data is to be inserted into a MySQL database.');
        $options->registerOption('file', 'This is the name of the CSV to be parsed', 'f', true);
        $options->registerOption('create_table',
            'This will cause the MySQL users table to be built (and no further action will be taken)');
        $options->registerOption('dry_run', 'This will be used with the --file directive in case we want to run the script but not insert
into the DB. All other functions will be executed, but the database won\'t be altered');
        $options->registerOption('username', 'MySQL username', 'u', true);
        $options->registerOption('password', 'MySQL password', 'p', true);
        $options->registerOption('host', 'MySQL host', 'h', true);
    }

    // implement your code
    protected function main(Options $options): void
    {
        try {

            if ($options->getOpt('u') && $options->getOpt('p') && $options->getOpt('h')) {
                $pdo = $this->createPDO( $options->getOpt('h'), $options->getOpt('u'), $options->getOpt('p'));
            } else {
                $pdo = $this->createPDO($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
            }

            if ($options->getOpt('create_table')) {

                $this->resetTable($pdo);

                $this->success('Users Table Created');
                return;
            }

            if ($file = $options->getOpt('file')) {
                $csv = new Csv();

                $data = $csv->parseFile($file);

                if (empty($data)) {
                    $this->error('csv file provided is not in correct format', compact('file'));
                }

                $this->createDB($pdo);
                $this->createTable($pdo);

                foreach ($data as $user) {

                    if (!$this->validateEmail($user['email'])) {
                        $this->error('Invalid user email:' . $user['email']);
                        continue;
                    }

                    $data = [
                        'name' => ucfirst(strtolower($user['name'])),
                        'surname' => ucfirst(strtolower($user['surname'])),
                        'email' => strtolower($user['email']),
                    ];

                    try {
                        $sql = "INSERT INTO `users` (name, surname, email) VALUES (:name, :surname, :email)";
                        $stmt= $pdo->prepare($sql);

                        if ($options->getOpt('dry_run')) {
                            $this->success('[DRY RUN] Successfully inserted a user with data ' . implode(',', $data));
                        } else {
                            $stmt->execute($data);
                            $this->success('Successfully inserted a user with data' . implode(',', $data));
                        }

                    } catch (\PDOException $e) {
                        $this->error($e->getMessage());
                        continue;
                    }

                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

    }


    private function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function createDB(\PDO $pdo): void
    {
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS users");
            $pdo->query("USE users");
            return;
        } catch (PDOException $e) {
            $this->error($e->getMessage());
        }
    }
    private function resetTable(\PDO $pdo): void
    {
        $this->createDB($pdo);
        $this->dropTable($pdo);
        $this->createTable($pdo);
    }

    private function createTable(\PDO $pdo): bool
    {
        try {
            return $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `surname` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) UNIQUE NOT NULL
            );");

        } catch (PDOException $e) {
            $this->error($e->getMessage());
        }

        return false;
    }

    private function dropTable(\PDO $pdo): void
    {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `users`");
            return;
        } catch (PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    private function createPDO(string $host, string $username, string $password): PDO
    {
        $dsn = "mysql:dbname=users;host=$host;charset=utf8mb4";
        return new PDO($dsn, $username, $password);
    }
}