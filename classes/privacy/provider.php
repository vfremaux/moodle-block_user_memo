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

namespace block_user_memo\privacy;

use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider {

    public static function get_metadata(collection $collection) : collection {

        $fields = [
            'userid' => 'privacy:metadata:user_memo:userid',
            'blockid' => 'privacy:metadata:user_memo:blockid',
            'memo' => 'privacy:metadata:user_memo:memo',
        ];

        $collection->add_database_table('block_user_memo', $fields, 'privacy:metadata:user_memo');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
 
        $sql = "
            SELECT
                c.id
            FROM
                {context} c
            INNER JOIN
                {block_instances} bi
            ON
                bi.id = c.instanceid AND
                c.contextlevel = :contextlevel
            LEFT JOIN
                {block_user_memo} bum
            ON
                bum.blockid = bi.id
            WHERE
                bum.userid = :userid AND
                bi.blockname = :blockname
        ";
 
        $params = [
            'blockname'           => 'user_memo',
            'contextlevel'      => CONTEXT_BLOCK,
            'userid'  => $userid,
        ];
 
        $contextlist->add_from_sql($sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $ctx) {
            $instance = writer::withcontext($ctx);

            $params = array('blockid' => $ctx->instanceid,
                            'userid' => $user->id);
            $memos = $DB->get_records('block_user_memo', $params, 'sortorder');
            $data = new StdClass;
            $data->memos = $memos;
            $data->userid = transform::user($data->userid);
            $data->timecreated = tranform::datetime($data->timecreated);
            $instance->export_data(null, $data);
        }
    }

    public static function delete_data_for_all_users_in_context(deletion_criteria $criteria) {
        global $DB;

        $context = $criteria->get_context();
        if (empty($context)) {
            return;
        }

        $DB->delete_records('block_user_memo', ['blockid' => $context->instanceid]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {
            $DB->delete_records('block_user_memo', ['blockid' => $ctx->instanceid, 'userid' => $userid]);
        }
    }
}