<?php
/*
 * Author: Erik Fox
 * Date Created: 5/22/18
 * Last Updated: 7/15/18
 */

$capabilities = array(
        'block/leaderboard:addinstance' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,
    
            'captype' => 'write',
            'contextlevel' => CONTEXT_BLOCK,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
    
            'clonepermissionsfrom' => 'moodle/site:manageblocks'
        ),
    
        'block/leaderboard:viewaddupdatemodule' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'user' => CAP_ALLOW
            )
        ),
    
        'block/leaderboard:viewdeletemodule' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'user' => CAP_ALLOW
            )
        ),

        'block/leaderboard:view' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'guest' => CAP_ALLOW,
                'user' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            )
        ),

        'block/leaderboard:submit' => array(
            'riskbitmask' => RISK_SPAM,
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => array(
                'student' => CAP_ALLOW
            )
        ),

);