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
 * @package   block_user_memo
 * @category  blocks
 * @author    Valery Fremaux (valery.fremaux@edunao.com)
 * @copyright 2015 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_user_memo_renderer extends plugin_renderer_base {

    protected $theblock;

    public function render_content(&$theblock) {
        global $DB, $USER, $CFG, $COURSE;

        $this->theblock = $theblock;

        $context = context_block::instance($this->theblock->instance->id);

        $str = '';

        $pageid = optional_param('page', 0, PARAM_INT);

        $thisformurl = new moodle_url('/course/view.php');
        $str .= '<form name="savememo" method="get" action="'.$thisformurl.'">';
        $str .= '<input type="hidden" name="id" value="'.$COURSE->id.'" />';
        $str .= '<input type="hidden" name="what" value="addmemo" />';
        $str .= '<input type="hidden" name="page" value="'.$pageid.'" />';
        $str .= '<input type="hidden" name="blockid" value="'.$this->theblock->instance->id.'" />';
        $str .= '<textarea name="memo"></textarea>';
        $str .= '<input type="submit" name="go_btn" value="'.get_string('addmemo', 'block_user_memo').'" />';
        $str .= '</form>';

        $params = array('blockid' => $theblock->instance->id, 'userid' => $USER->id);
        $memos = $DB->get_records('block_user_memo', $params, 'sortorder');
        if ($memos) {
            foreach ($memos as $memo) {
                $str .= $this->render_memo($memo);
            }
        }

        if ($CFG->enableblogs && has_capability('moodle/blog:view', $context) && $memos) {
            $str .= $this->output->box_start('user-memo-exporters');
            $params = array('id' => $COURSE->id, 'what' => 'exporttoblog', 'blockid' => $this->theblock->instance->id);
            $exporturl = new moodle_url(me(), $params);
            $str .= $this->output->single_button($exporturl, get_string('exporttoblog', 'block_user_memo'));

            $params = array('id' => $COURSE->id, 'what' => 'clearmemo', 'blockid' => $this->theblock->instance->id);
            $clearurl = new moodle_url(me(), $params);
            $str .= $this->output->single_button($clearurl, get_string('clear'));
            $str .= $this->output->box_end();
        }

        return $str;
    }

    public function render_memo($memo) {
        global $COURSE;

        $str = '';

        $params = array('id' => $COURSE->id, 'what' => 'deletememo', 'memoid' => $memo->id);
        $deleteurl = new moodle_url(new moodle_url('/course/view.php'), $params);
        $commands = '<a href="'.$deleteurl.'">'.$this->output->pix_icon('t/delete', get_string('delete'), 'moodle').'</a>';

        $str .= '<div class="user-memo">';
        $str .= '<div class="user-memo-controls">';
        $str .= $commands;
        $str .= '</div>';
        $str .= '<div class="user-memo-text">';
        $str .= $memo->memo;
        $str .= '</div>';
        $str .= '<div class="user-memo-date">';
        $str .= userdate($memo->timecreated);
        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }
}