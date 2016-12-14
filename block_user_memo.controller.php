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
 * @package block_user_memo
 * @category  blocks
 * @author  Valery Fremaux (valery.fremaux@edunao.com)
 * @copyright  2015 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_user_memo_controller {

    protected $theblock;

    protected $data;

    protected $received;

    public function __construct($theblock) {
        $this->theblock = $theblock;
    }

    /**
     * This is an early form of a controller, introducing testability.
     * @TODO : unify controller signatures with more common practices
     * @see local_shop controllers.
     */
    public function receive($cmd, $data = null) {

        if (!empty($data)) {
            // Data is fed from outside.
            $this->data = (object)$data;
            return;
        } else {
            $this->data = new \StdClass;
        }

        switch ($cmd) {
            case 'deletememo':
                $this->data->memoid = required_param('memoid', PARAM_INT);
                break;

            case 'clearmemo':
                break;

            case 'addmemo':
                $this->data->memo = required_param('memo', PARAM_CLEANHTML);
                break;

            case 'exporttoblog':
                break;
            default:
        }

        $this->received = true;
    }

    public function process($cmd, $userid = null, $courseid = null) {
        global $DB, $USER, $COURSE;

        if (!$this->received) {
            throw new \coding_exception('Data must be received in controller before operation. this is a programming error.');
        }

        if (empty($userid)) {
            $userid = $USER->id;
        }
        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }

        $context = context_block::instance($this->theblock->instance->id);

        // Perform controller.
        if ('deletememo' == $cmd) {
            $DB->delete_records('block_user_memo', array('id' => $this->data->memoid));
        }

        if ('clearmemo' == $cmd) {
            $DB->delete_records('block_user_memo', array('blockid' => $this->theblock->instance->id, 'userid' => $userid));
            if (!defined('PHPUNIT_TEST')) {
                redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
            }
        }

        if ('addmemo' == $cmd) {
            $sqlparams = array('blockid' => $this->theblock->instance->id, 'userid' => $userid);
            $lastorder = $DB->get_field('block_user_memo', 'MAX(sortorder)', $sqlparams);
            $lastorder = 0 + @$lastorder + 1;
            $newmemo = new StdClass;
            $newmemo->memo = $this->data->memo;
            $newmemo->userid = $userid;
            $newmemo->blockid = $this->theblock->instance->id;
            $newmemo->timecreated = time();
            $newmemo->sortorder = $lastorder;
            $memoid = $DB->insert_record('block_user_memo', $newmemo);
            if (!defined('PHPUNIT_TEST')) {
                redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
            }
        }

        if ('exporttoblog' == $cmd) {
            if (has_capability('moodle/blog:view', $context)) {
                block_user_memo::export_to_blog($userid, $this->theblock->instance->id);
                $this->theblock->controllermessage = get_string('exportedtoblog', 'block_user_memo');
            }
        }
    }
}