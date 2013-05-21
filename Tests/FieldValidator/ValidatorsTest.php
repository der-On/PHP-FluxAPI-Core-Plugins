<?php
require_once __DIR__ . '/../FluxApi_Database_TestCase.php';

class ValidatorsTest extends FluxApi_Database_TestCase
{
    public function testValidUser()
    {
        $user = self::$fluxApi->createUser(array(
            'username' => 'foo',
            'email' => 'foo@bar.com',
            'password' => 'foobar'
        ));

        self::$fluxApi->saveUser($user);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUserInvalidEmail()
    {
        $user = self::$fluxApi->createUser(array(
            'username' => 'foo',
            'password' => 'foobar',
            'email' => 'foo[at]bar'
        ));

        self::$fluxApi->saveUser($user);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUserMissingUsername()
    {
        $user = self::$fluxApi->createUser(array(
            'password' => 'foobar',
            'email' => 'foo@bar.com'
        ));

        self::$fluxApi->saveUser($user);
    }
}