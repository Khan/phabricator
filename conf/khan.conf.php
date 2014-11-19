<?php

// Sure, i mean, you *could* update this file, but wouldn't you rather update
// a db via a shiny web interface instead? Who's to say what's right or wrong
// anymore? Does this file even do anything anymore? Does anybody even read
// these comments? Are you still there? Where is everyone? It's getting so
// cold here... Won't you join me by the fire? Hello? hello? daisy... daisyyy
//
// http://phabricator.khanacademy.org/config/

return array(

  // This will be the base domain for your install, and must be configured.
  // Use "https://" if you have SSL. See below for some notes.
  'phabricator.base-uri' => 'http://phabricator.khanacademy.org/',

  // Where we put things on local disks (logs, files, etc)
  'log.access.path' => '/home/ubuntu/logs/phabricator.log',
  'phd.log-directory' => '/home/ubuntu/logs/phd-daemons',
  'repository.default-local-path' => '/home/ubuntu/phabricator/repositories/git',
  'storage.local-disk.path' => '/home/ubuntu/phabricator/files',

  // We trust our code-authors to not close an audit unless there's
  // a good reason.
  'audit.can-author-close-audit' => true,

  // Only allow people at khanacademy.org to register.
  'auth.email-domains' => array(
    'khanacademy.org',
  ),

  // Let people debug if they want (though don't profile by default).
  'darkconsole.enabled' => true,
  'debug.profile-rate' => 0,

  // Allow, but don't require, a user to say how they did testing.
  'differential.require-test-plan-field' => false,
  'differential.allow-reopen' => true,

  // Tell hipchat about phabricator reviews being created, and the like.
  'feed.http-hooks' => array(
     'http://khan-webhooks.appspot.com/phabricator-feed',
  ),

  // Custom Maniphest fields
  'maniphest.custom-field-definitions' => array(
    'khan:duedate' => array(
      'name'       => 'Target Completion Date',
      'type'       => 'date',
      'caption'    => 'The date you expect to have this task completed by',
      'required'   => false,
    ),
    // TODO(tom): Link to a document with more information about what the error
    // key signifies
    'khan:errorkey' => array(
      'name'       => 'Error key',
      'type'       => 'text',
      'caption'    => 'Associated error key; prepopulated by /devadmin/errors',
      'required'   => false,
    )
  ),

  // Basic email domain configuration.
  'metamta.default-address' => 'noreply@phabricator.khanacademy.org',
  'metamta.domain'          => 'phabricator.khanacademy.org',
  'metamta.can-send-as-user'    => true,
  'metamta.user-address-format' => 'short',
  // gmail threading will break if the subject changes.
  'metamta.vary-subjects' => false,

  // Connection information for MySQL.
  'mysql.host' => 'localhost',
  'mysql.user' => 'phabricator',
  'mysql.pass' => 'codereview',

  // Global phabricator options, such as the timezone for khan academy.
  'phabricator.timezone'    => 'America/Los_Angeles',
  'phabricator.csrf-key'    => '0016ee009c31da52bc9044dd5a989ff1ec6da80',

  // source-code highlighting is the bomb
  'pygments.enabled'            => true,

  // TODO(csilvers): enable recaptcha if brute-force attacks become a problem.

  // Docs say this is "pretty silly (but sort of awesome)". Good enough for me.
  'remarkup.enable-embedded-youtube' => true,

  // This apparently avoids some cookie-based attacks.
  'security.alternate-file-domain'  => 'https://phabricator-files.khanacademy.org/',

  // Let people upload giant files.
  'storage.mysql-engine.max-size' => 0,
  'storage.upload-size-limit' => '20M',

  // pygments doesn't know .q files are sql or that jsx is javascript(-ish).
  // We add that.  (The .arcconfig comes default.conf; I'm not sure if
  // read_config does merging on sub-arrays properly, so I just repeat it to be
  // safe.)
  'syntax.filemap' => array(
    '@\.arcconfig$@' => 'js',
    '@\.q$@' => 'mysql',
    '@\.jsx$@' => 'js',
  ),

  // We use phabricator as a mini-LDAP system.
  'user.custom-field-definitions' => array(
    'khan:github-username' => array(
      'name' => 'Github username',
      'type' => 'text',
      'caption' => 'Your username on GitHub',
     ),
    'khan:hipchat-username' => array(
      'name' => 'Hipchat username',
      'type' => 'text',
      'caption' => 'Your username on Hipchat (do not include the `@`)',
    ),
  ),

  // List of file regexps that should be treated as if they are generated by
  // an automatic process, and thus get hidden by default in differential.
  'differential.generated-paths' => array(
    // NOTE(jeresig): Hides the build directory from the live-editor repo
    '#^build/#',
  ),

);
