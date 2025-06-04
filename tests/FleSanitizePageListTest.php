<?php
use PHPUnit\Framework\TestCase;

class FleSanitizePageListTest extends TestCase
{
    protected function setUp(): void
    {
        $_POST = [];
        $GLOBALS['options'] = [];
    }

    public function test_add_ids_merges_with_existing()
    {
        $GLOBALS['options']['fle_allowed_pages'] = '1,2';
        $_POST['fle_allowed_pages_action'] = 'add';

        $result = fle_sanitize_page_list(['2', 3, 4]);
        $this->assertSame('1,2,3,4', $result);
    }

    public function test_remove_ids_subtracts_from_existing()
    {
        $GLOBALS['options']['fle_allowed_pages'] = '1,2,3';
        $_POST['fle_allowed_pages_action'] = 'remove';

        $result = fle_sanitize_page_list([2]);
        $this->assertSame('1,3', $result);
    }

    public function test_invalid_values_are_sanitized()
    {
        $GLOBALS['options']['fle_allowed_pages'] = '5';
        $_POST['fle_allowed_pages_action'] = 'add';

        $result = fle_sanitize_page_list([-3, '5', 5, 0]);
        $this->assertSame('5,3', $result);
    }
}
