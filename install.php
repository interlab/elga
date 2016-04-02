<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK')) {
    require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('ELK')) {
    die('<b>Error:</b> Cannot install - please verify you put this in the same place as ELK\'s index.php.');
}

// global $db_prefix, $db_package_log;

$db = database();
$db_table = db_table();

$tables = array(
    'elga_albums' => array(
        'columns' => array(
            array('name' => 'id', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true),
            array('name' => 'name', 'type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
            array('name' => 'description', 'null' => false, 'type' => 'text'),
            array('name' => 'icon', 'type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
            array('name' => 'leftkey', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0),
            array('name' => 'rightkey', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0),
        ),
        'indexes' => array(
            array('type' => 'primary', 'columns' => array('id')),
            array('type' => 'index', 'columns' => array('leftkey'), 'name' => 'leftkey'),
            array('type' => 'index', 'columns' => array('rightkey'), 'name' => 'rightkey'),
            array('type' => 'index', 'columns' => array('leftkey', 'rightkey'), 'name' => 'leftright'),
        ),
    ),
    'elga_files' => array(
        'columns' => array(
            array('name' => 'id', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true),
            array('name' => 'orig_name', 'type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
            array('name' => 'fname', 'type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
            array('name' => 'thumb', 'type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
            array('name' => 'preview', 'type' => 'varchar', 'size' => 255, 'null' => false, 'default' => ''),
            array('name' => 'fsize', 'type' => 'bigint', 'size' => 20, 'unsigned' => true, 'null' => false),
            array('name' => 'id_album', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'null' => false),
            array('name' => 'title', 'type' => 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
            array('name' => 'description', 'type' => 'text', 'null' => false),
            array('name' => 'views', 'type' => 'int', 'size' => 10, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'downloads', 'type' => 'int', 'size' => 10, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'id_last_comment', 'type' => 'int', 'size' => 10, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'comments', 'type' => 'int', 'size' => 10, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'id_member', 'type' => 'int', 'size' => 10, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'member_name', 'type' => 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
            array('name' => 'time_added', 'type' => 'int', 'size' => 10, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'approved', 'type' => 'tinyint', 'size' => 1, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'last_edited', 'type' => 'int', 'size' => 10, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'last_edited_by', 'type' => 'int', 'size' => 10, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'exif', 'type' => 'text', 'null' => false),
            array('name' => 'width', 'type' => 'tinyint', 'size' => 4, 'null' => false, 'unsigned' => true, 'default' => 0),
            array('name' => 'height', 'type' => 'tinyint', 'size' => 4, 'null' => false, 'unsigned' => true, 'default' => 0),
        ),
        'indexes' => array(
            array('type' => 'primary', 'columns' => array('id')),
            array('type' => 'index', 'columns' => array('id', 'id_album'), 'name' => 'filealbum'),
            array('type' => 'index', 'columns' => array('id_album'), 'name' => 'idalbum'),
        ),
    ),
);

foreach ($tables as $table => $data) {
    $db_table->db_create_table('{db_prefix}' . $table, $data['columns'], $data['indexes'], [], 'ignore');
}

$result = $db->query('', '
    SELECT id
    FROM {db_prefix}elga_albums
    LIMIT 1',
    []
);
list ($has_album) = $db->fetch_row($result);
$db->free_result($result);

if (empty($has_album))
{
    $albums = [
        ['name' => 'Юмор', 'description' => 'LOL', 'icon' => 'clown.png', 'leftkey' => '1', 'rightkey' => '2',],
        ['name' => 'Демотиваторы', 'description' => 'хо-хо', 'icon' => 'bomb.png', 'leftkey' => '3', 'rightkey' => '4',],
        ['name' => 'Природа', 'description' => 'zoo', 'icon' => 'butterfly.png', 'leftkey' => '5', 'rightkey' => '6',],
        ['name' => 'Города', 'description' => 'city', 'icon' => 'paris-eiffel.png',  'leftkey' => '7', 'rightkey' => '8',],
        ['name' => 'Girls', 'description' => 'sexy', 'icon' => 'girl.png',  'leftkey' => '9', 'rightkey' => '10',],
    ];

    $db->insert('ignore',
        '{db_prefix}elga_albums',
        ['name' => 'text', 'description' => 'text', 'icon' => 'text', 'leftkey' => 'int', 'rightkey' => 'int'],
        $albums,
        ['id',]
    );
}
