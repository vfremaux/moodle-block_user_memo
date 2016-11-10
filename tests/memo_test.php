<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Events tests.
 *
 * @package    block_user_memo
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/user_memo/block_user_memo.php');
require_once($CFG->dirroot.'/lib/blocklib.php');

global $CFG;

/**
 * Events tests class.
 *
 * @package    block_user_memo
 * @copyright  2013 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_user_memo_memo_testcase extends advanced_testcase {

    protected $course;

    protected $blockinstance;

    /**
     * Setup often used objects for the following tests.
     */
    protected function setup() {
        global $DB;

        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($this->course->id);
        $record = new StdClass;
        $record->parentcontext = $context->id;
        $record->blockname = 'user_memo';
        $record->showinsubcontexts = '';
        $record->pagetypepattern = '*';
        $record->subpagepattern = '*';

        $blockrecord = $this->getDataGenerator()->create_block('user_memo', $record);
        $this->blockinstance = block_instance('user_memo', $blockrecord);
        $this->context = context_block::instance($this->blockinstance->instance->id);
    }

    public function test_memo() {
        global $DB, $CFG;

        // Generate user data.
        $user = $this->getDataGenerator()->create_user();

        $beforememos = $DB->count_records('block_user_memo', array());

        require_once($CFG->dirroot.'/blocks/user_memo/block_user_memo.controller.php');
        $controller = new block_user_memo_controller($this->blockinstance);
        $params[] = "This is a test memo";
        $controller->handle('addmemo', $params);
        $params[] = "This is a test memo 2";
        $controller->handle('addmemo', $params);
        $params[] = "This is a test memo 3";
        $controller->handle('addmemo', $params);

        $this->assertEquals($beforememos + 3, $DB->count_records('block_user_memo', array()));
        $this->assertEquals(3, $DB->count_records('block_user_memo', array('blockid' => $this->blockinstance->instance->id)));

        $params[] = $this->blockinstance->instance->id;
        $controller->handle('clearmemo', $params);

        $this->assertEquals($beforememos, $DB->count_records('block_user_memo', array()));
        $this->assertEquals(0, $DB->count_records('block_user_memo', array('blockid' => $this->blockinstance->instance->id)));

    }
}