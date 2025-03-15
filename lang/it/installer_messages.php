<?php

return [

    /*
     *
     * Shared translations.
     *
     */
    'title' => 'Installatore Laravel',
    'next' => 'Prossimo Passo',
    'back' => 'Precedente',
    'finish' => 'Installa',
    'forms' => [
        'errorTitle' => 'Si sono verificati i seguenti errori:',
    ],

    /*
     *
     * Home page translations.
     *
     */
    'welcome' => [
        'templateTitle' => 'Benvenuto',
        'title' => 'Installatore Laravel',
        'message' => 'Installazione e configurazione facile.',
        'next' => 'Verifica Requisiti',
    ],

    /*
     *
     * Requirements page translations.
     *
     */
    'requirements' => [
        'templateTitle' => 'Passo 1 | Requisiti del Server',
        'title' => 'Requisiti del Server',
        'next' => 'Verifica Permessi',
    ],

    /*
     *
     * Permissions page translations.
     *
     */
    'permissions' => [
        'templateTitle' => 'Passo 2 | Permessi',
        'title' => 'Permessi',
        'next' => 'Configura Ambiente',
    ],

    /*
     *
     * Environment page translations.
     *
     */
    'environment' => [
        'menu' => [
            'templateTitle' => 'Passo 3 | Impostazioni Ambiente',
            'title' => 'Impostazioni Ambiente',
            'desc' => 'Seleziona come configurare il file <code>.env</code> dell\'app.',
            'wizard-button' => 'Configurazione Guidata',
            'classic-button' => 'Editor di Testo Classico',
        ],
        'wizard' => [
            'templateTitle' => 'Passo 3 | Impostazioni Ambiente | Guida',
            'title' => 'Guida <code>.env</code>',
            'tabs' => [
                'environment' => 'Ambiente',
                'database' => 'Database',
                'application' => 'Applicazione',
            ],
            'form' => [
                'name_required' => 'È richiesto un nome per l\'ambiente.',
                'app_name_label' => 'Nome App',
                'app_name_placeholder' => 'Nome App',
                'app_environment_label' => 'Ambiente App',
                'app_environment_label_local' => 'Locale',
                'app_environment_label_developement' => 'Sviluppo',
                'app_environment_label_qa' => 'Qa',
                'app_environment_label_production' => 'Produzione',
                'app_environment_label_other' => 'Altro',
                'app_environment_placeholder_other' => 'Inserisci il tuo ambiente...',
                'app_debug_label' => 'Debug App',
                'app_debug_label_true' => 'Vero',
                'app_debug_label_false' => 'Falso',
                'app_log_level_label' => 'Livello di Log App',
                'app_log_level_label_debug' => 'debug',
                'app_log_level_label_info' => 'info',
                'app_log_level_label_notice' => 'notice',
                'app_log_level_label_warning' => 'warning',
                'app_log_level_label_error' => 'error',
                'app_log_level_label_critical' => 'critical',
                'app_log_level_label_alert' => 'alert',
                'app_log_level_label_emergency' => 'emergency',
                'app_url_label' => 'URL App',
                'app_url_placeholder' => 'URL App',
                'db_connection_failed' => 'Impossibile connettersi al database.',
                'db_connection_label' => 'Connessione Database',
                'db_connection_label_mysql' => 'mysql',
                'db_connection_label_sqlite' => 'sqlite',
                'db_connection_label_pgsql' => 'pgsql',
                'db_connection_label_sqlsrv' => 'sqlsrv',
                'db_host_label' => 'Host Database',
                'db_host_placeholder' => 'Host Database',
                'db_port_label' => 'Porta Database',
                'db_port_placeholder' => 'Porta Database',
                'db_name_label' => 'Nome Database',
                'db_name_placeholder' => 'Nome Database',
                'db_username_label' => 'Nome Utente Database',
                'db_username_placeholder' => 'Nome Utente Database',
                'db_password_label' => 'Password Database',
                'db_password_placeholder' => 'Password Database',

                'app_tabs' => [
                    'more_info' => 'Maggiori Informazioni',
                    'broadcasting_title' => 'Broadcasting, Caching, Sessione, &amp; Coda',
                    'broadcasting_label' => 'Driver di Broadcast',
                    'broadcasting_placeholder' => 'Driver di Broadcast',
                    'cache_label' => 'Driver di Cache',
                    'cache_placeholder' => 'Driver di Cache',
                    'session_label' => 'Driver di Sessione',
                    'session_placeholder' => 'Driver di Sessione',
                    'queue_label' => 'Driver di Coda',
                    'queue_placeholder' => 'Driver di Coda',
                    'redis_label' => 'Driver di Redis',
                    'redis_host' => 'Host Redis',
                    'redis_password' => 'Password Redis',
                    'redis_port' => 'Porta Redis',

                    'mail_label' => 'Mail',
                    'mail_driver_label' => 'Driver di Mail',
                    'mail_driver_placeholder' => 'Driver di Mail',
                    'mail_host_label' => 'Host Mail',
                    'mail_host_placeholder' => 'Host Mail',
                    'mail_port_label' => 'Porta Mail',
                    'mail_port_placeholder' => 'Porta Mail',
                    'mail_username_label' => 'Nome Utente Mail',
                    'mail_username_placeholder' => 'Nome Utente Mail',
                    'mail_password_label' => 'Password Mail',
                    'mail_password_placeholder' => 'Password Mail',
                    'mail_encryption_label' => 'Crittografia Mail',
                    'mail_encryption_placeholder' => 'Crittografia Mail',

                    'pusher_label' => 'Pusher',
                    'pusher_app_id_label' => 'ID App Pusher',
                    'pusher_app_id_palceholder' => 'ID App Pusher',
                    'pusher_app_key_label' => 'Chiave App Pusher',
                    'pusher_app_key_palceholder' => 'Chiave App Pusher',
                    'pusher_app_secret_label' => 'Segreto App Pusher',
                    'pusher_app_secret_palceholder' => 'Segreto App Pusher',
                ],
                'buttons' => [
                    'setup_database' => 'Configura Database',
                    'setup_application' => 'Configura Applicazione',
                    'install' => 'Installa',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'Passo 3 | Impostazioni Ambiente | Editor Classico',
            'title' => 'Editor Ambiente Classico',
            'save' => 'Salva .env',
            'back' => 'Usa Configurazione Guidata',
            'install' => 'Salva e Installa',
        ],
        'success' => 'Le impostazioni del file .env sono state salvate.',
        'errors' => 'Impossibile salvare il file .env, crealo manualmente.',
    ],

    'install' => 'Installa',

    /*
     *
     * Installed Log translations.
     *
     */
    'installed' => [
        'success_log_message' => 'Installatore Laravel installato con successo il ',
    ],

    /*
     *
     * Final page translations.
     *
     */
    'final' => [
        'title' => 'Installazione Completata',
        'templateTitle' => 'Installazione Completata',
        'finished' => 'L\'applicazione è stata installata con successo.',
        'migration' => 'Output Console Migrazione &amp; Seed:',
        'console' => 'Output Console Applicazione:',
        'log' => 'Voce di Log Installazione:',
        'env' => 'File .env Finale:',
        'exit' => 'Clicca qui per uscire',
        'user_website' => 'Sito Utente',
        'admin_panel' => 'Pannello Admin',
    ],

    /*
     *
     * Update specific translations
     *
     */
    'updater' => [
        /*
         *
         * Shared translations.
         *
         */
        'title' => 'Aggiornamento Laravel',

        /*
         *
         * Welcome page translations for update feature.
         *
         */
        'welcome' => [
            'title' => 'Benvenuto nell\'Aggiornamento',
            'message' => 'Benvenuto nella guida all\'aggiornamento.',
        ],

        /*
         *
         * Overview page translations for update feature.
         *
         */
        'overview' => [
            'title' => 'Panoramica',
            'message' => 'C\'è 1 aggiornamento.|Ci sono :number aggiornamenti.',
            'install_updates' => 'Installa Aggiornamenti',
        ],

        /*
         *
         * Final page translations.
         *
         */
        'final' => [
            'title' => 'Completato',
            'finished' => 'Il database dell\'applicazione è stato aggiornato con successo.',
            'exit' => 'Clicca qui per uscire',
        ],

        'log' => [
            'success_message' => 'Installatore Laravel aggiornato con successo il ',
        ],
    ],
];
