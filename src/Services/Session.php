<?php
namespace Frogsystem\Legacy\Bridge\Services;

class Session {

    function __construct()
    {
        // Start Session
        session_start();

        // Init some Session values
        $_SESSION['user_level'] = !isset($_SESSION['user_level']) ? 'unknown' : $_SESSION['user_level'];

        //TODO: Session Init Hook
    }
}
