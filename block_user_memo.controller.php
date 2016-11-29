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

    public function __construct($theblock) {
        $this->theblock = $theblock;
    }

    /**
     * This is an early form of a controller, introducing testability.
     * @TODO : unify controller signatures with more common practices
     * @see local_shop controllers.
     */
    public function get_params($action) {

        $params = array();

        switch ($action) {
            case 'deletememo':
                $params[] = required_param('memoid', PARAM_INT);
                break;

            case 'clearmemo':
                $params[] = required_param('blockid', PARAM_INT);
                break;

            case 'addmemo':
                $params[] = required_param('memo', PARAM_CLEANHTML);
                break;

            case 'exporttoblog':
                $params[] = required_param('blockid', PARAM_INT);
                break;
            default:
        }

        return $params;
    }

    public function handle($action, $params, $userid = null, $courseid = null) {
        global $DB, $USER, $COURSE;

        if (empty($userid)) {
            $userid = $USER->id;
        }
        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }

        $context = context_block::instance($this->theblock->instance->id);

        // Perform controller.
        if ('deletememo' == $action) {
            $todelete = array_shift($params);
            $DB->delete_records('block_user_memo', array('id' => $todelete));
        }

        if ('clearmemo' == $action) {
            $todelete = array_shift($params);
            $DB->delete_records('block_user_memo', array('blockid' => $todelete, 'userid' => $userid));
            if (!defined('PHPUNIT_TEST')) {
                redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
            }
        }

        if ('addmemo' == $action) {
            $params = array('blockid' => $this->theblock->instance->id, 'userid' => $userid);
            $lastorder = $DB->get_field('block_user_memo', 'MAX(sortorder)', $params);
            $lastorder = 0 + @$lastorder + 1;
            $newmemo = new StdClass;
            $newmemo->memo = array_shift($params);
            $newmemo->userid = $userid;
            $newmemo->blockid = $this->theblock->instance->id;
            $newmemo->timecreated = time();
            $newmemo->sortorder = $lastorder;
            $memoid = $DB->insert_record('block_user_memo', $newmemo);
            if (!defined('PHPUNIT_TEST')) {
                redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
            }
        }

        if ('exporttoblog' == $action) {
            $blockid = array_shift($params);
            if (has_capability('moodle/blog:view', $context)) {
                block_user_memo::export_to_blog($userid, $blockid);
                $this->theblock->controllermessage = get_string('exportedtoblog', 'block_user_memo');
            }
        }
    }
}