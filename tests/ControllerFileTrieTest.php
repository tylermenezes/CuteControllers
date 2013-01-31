<?php

require_once(dirname(__FILE__) . '/../CuteControllers/ControllerFileTrie.php');

class ControllerFileTrieTest extends PHPUnit_Framework_TestCase
{
    public function test_get_nearest_match()
    {
        try {
            unlink('running_test/folder/file/index.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test/folder/file');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder/file.php');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder/index.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test/folder');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test');
        } catch (\Exception $ex) {}

        mkdir('running_test');
        mkdir('running_test/folder');
        mkdir('running_test/folder/file');
        file_put_contents('running_test/folder/file/index.php', '<?php class w { public $whoami = "w"; }');
        file_put_contents('running_test/folder/file.php', '<?php class x { public $whoami = "x"; }');
        file_put_contents('running_test/folder/index.php', '<?php class y { public $whoami = "y"; }');
        file_put_contents('running_test/folder.php', '<?php class z { public $whoami = "z"; }');
        $trie = new \CuteControllers\ControllerFileTrie('/folder/file.html');

        $this->assertEquals('folder/file/index.php', $trie->find_closest_filesystem_match('running_test/')->matched_path, 'Failed routing to x/y/index.php');
        unlink('running_test/folder/file/index.php');
        $this->assertEquals('folder/file.php', $trie->find_closest_filesystem_match('running_test/')->matched_path, 'Failed routing to x/y.php');
        rmdir('running_test/folder/file');
        $this->assertEquals('folder/file.php', $trie->find_closest_filesystem_match('running_test/')->matched_path, 'Failed routing to x/y.php');
        unlink('running_test/folder/file.php');
        $this->assertEquals('folder/index.php', $trie->find_closest_filesystem_match('running_test/')->matched_path, 'Failed routing to x/index.php');
        unlink('running_test/folder/index.php');
        $this->assertEquals('folder.php', $trie->find_closest_filesystem_match('running_test/')->matched_path, 'Failed routing to x.php');
        rmdir('running_test/folder');
        $this->assertEquals('folder.php', $trie->find_closest_filesystem_match('running_test/')->matched_path, 'Failed routing to x.php');
        unlink('running_test/folder.php');
        rmdir('running_test');
    }

    public function test_get_unmatched()
    {
        try {
            unlink('running_test/folder/file/file2/index.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test/folder/file/file2');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder/file/index.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test/folder/file');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder/file.php');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder/index.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test/folder');
        } catch (\Exception $ex) {}
        try {
            unlink('running_test/folder.php');
        } catch (\Exception $ex) {}
        try {
            rmdir('running_test');
        } catch (\Exception $ex) {}

        mkdir('running_test');
        mkdir('running_test/folder');
        mkdir('running_test/folder/file');
        mkdir('running_test/folder/file/file2');
        file_put_contents('running_test/folder/file/file2/index.php', '<?php class w { public $whoami = "w"; }');
        file_put_contents('running_test/folder/file/index.php', '<?php class w { public $whoami = "w"; }');
        file_put_contents('running_test/folder/file.php', '<?php class x { public $whoami = "x"; }');
        file_put_contents('running_test/folder/index.php', '<?php class y { public $whoami = "y"; }');
        file_put_contents('running_test/folder.php', '<?php class z { public $whoami = "z"; }');
        $trie = new \CuteControllers\ControllerFileTrie('/folder/file/file2.html');

        $this->assertEquals('', $trie->find_closest_filesystem_match('running_test/')->unmatched_path);
        unlink('running_test/folder/file/file2/index.php');
        rmdir('running_test/folder/file/file2');
        $this->assertEquals('file2', $trie->find_closest_filesystem_match('running_test/')->unmatched_path);
        unlink('running_test/folder/file/index.php');
        $this->assertEquals('file2', $trie->find_closest_filesystem_match('running_test/')->unmatched_path);
        rmdir('running_test/folder/file');
        $this->assertEquals('file2', $trie->find_closest_filesystem_match('running_test/')->unmatched_path);
        unlink('running_test/folder/file.php');
        $this->assertEquals('file/file2', $trie->find_closest_filesystem_match('running_test/')->unmatched_path);
        unlink('running_test/folder/index.php');
        $this->assertEquals('file/file2', $trie->find_closest_filesystem_match('running_test/')->unmatched_path);
        rmdir('running_test/folder');
        $this->assertEquals('file/file2', $trie->find_closest_filesystem_match('running_test/')->unmatched_path);
        unlink('running_test/folder.php');
        rmdir('running_test');
    }
}
