<?php


use Phinx\Seed\AbstractSeed;

class UsersSeeder extends AbstractSeed
{
    public function run()
    {
        $data = [
            'id' => '90e60becf3d5f385a9c07691109701f6',
            'identity' => 'opgcasper@gmail.com',
            'password_hash' => '$2y$10$LrPjdZqhojxq6etVitrQa.aL0TBef8ikyi7TP7egxblFKxNtAmANi',
            'activation_token' => '',
            'active' => true,
            'failed_login_attempts' => 0,
            'created' => '2019-01-17 18:09:44.39316',
            'updated' => '2019-01-17 18:09:44.39316',
            'activated' => '2019-01-17 18:09:44.39316',
            'last_login' => '2019-01-17 18:09:44.39316',
            'last_failed_login' => '2019-01-17 18:09:44.39316',
            'deleted' => null,
            'inactivity_flags' => null,
            'auth_token' => null,
            'email_update_request' => null,
            'password_reset_token' => null,
            'profile' => '{
                "dob": {"date": "1980-01-01T00:00:00.000000+0000"},
                "name": {"last": "Bobson", "first": "Bob", "title": "Mr"},
                "email": {"address": "opgcasper@gmail.com"},
                "address": {"address1": "34 A Road", "address2": "", "address3": "", "postcode": "T56 6TY"}
            }',
        ];

        $users = $this->table('users');

        if (!$this->fetchRow("SELECT * FROM users WHERE id='" . $data['id'] . "'")) {
            $users->insert($data)->save();
        }
    }
}
