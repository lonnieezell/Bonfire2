<?php

namespace Bonfire\Users\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class Seed100Users extends Seeder
{
    public function run()
    {
        $faker  = Factory::create();
        $groups = ['user', 'beta', 'developer'];

        for ($i = 0; $i < 100; $i++) {
            $timestamp = $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s');
            $firstName = $faker->firstName;
            $lastName  = $faker->lastName;
            $username  = substr($this->toAscii($firstName), 0, 5) . substr($this->toAscii($lastName), 0, 5);
            $active    = $faker->boolean;
            $email     = strtolower($this->toAscii($firstName) . '.' . $this->toAscii($lastName) . '@example.com');
            $secret2   = $faker->sha256;

            // Insert into users table
            $this->db->table('users')->insert([
                'first_name'  => $firstName,
                'last_name'   => $lastName,
                'username'    => $username,
                'active'      => $active,
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
                'last_active' => $timestamp,
            ]);

            // Get the last inserted ID
            $userId = $this->db->insertID();

            // Insert into auth_identities table
            $this->db->table('auth_identities')->insert([
                'user_id' => $userId,
                'type'    => 'email_password',
                'secret'  => $email,
                'secret2' => $secret2,
            ]);

            // Insert into auth_groups_users table
            $this->db->table('auth_groups_users')->insert([
                'user_id'    => $userId,
                'group'      => $faker->randomElement($groups),
                'created_at' => $timestamp,
            ]);
        }
    }

    private function toAscii($str)
    {
        return iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    }
}
