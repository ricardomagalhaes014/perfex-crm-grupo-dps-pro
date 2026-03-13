<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Project_name_patterns_model extends CI_Model
{
    private $project_name_patterns;

    public function __construct()
    {
        parent::__construct();
        $this->project_name_patterns = json_decode(get_option(POLYUTILITIES_PROJECT_NAME_PATTERNS), true) ?: [];
    }

    public function add($name, $note, $active, $created_by, $updated_by)
    {
        if (empty($name) || empty($created_by)) {
            return false;
        }

        $new_template = [
            'id' => uniqid(),
            'name' => $name,
            'note' => $note,
            'active' => $active,
            'timestamp' => time(),
            'created_by' => $created_by,
            'updated_by' => $updated_by,
        ];

        $this->project_name_patterns[] = $new_template;
        return update_option(POLYUTILITIES_PROJECT_NAME_PATTERNS, json_encode($this->project_name_patterns));
    }

    public function is_existed($name)
    {
        foreach ($this->project_name_patterns as $template) {
            if (strtolower($template['name']) === strtolower($name)) {
                return true;
            }
        }
        return false;
    }

    public function delete_project_name_pattern($id)
    {
        foreach ($this->project_name_patterns as $key => $template) {
            if ($template['id'] === $id) {
                unset($this->project_name_patterns[$key]);
                $this->project_name_patterns = array_values($this->project_name_patterns);
                return update_option(POLYUTILITIES_PROJECT_NAME_PATTERNS, json_encode($this->project_name_patterns));
            }
        }
        return false;
    }

    public function update($id, $name = null, $note = null, $active = null, $updated_by = null)
    {
        foreach ($this->project_name_patterns as &$pattern) {
            if ($pattern['id'] === $id) {
                if ($name !== null) {
                    $pattern['name'] = $name;
                }
                if ($note !== null) {
                    $pattern['note'] = $note;
                }
                if ($active !== null) {
                    $pattern['active'] = $active;
                }
                if ($updated_by !== null) {
                    $pattern['updated_by'] = $updated_by;
                }
                $pattern['timestamp'] = time();
                return update_option(POLYUTILITIES_PROJECT_NAME_PATTERNS, json_encode($this->project_name_patterns));
            }
        }
        return false;
    }

    public function get_all($active = null)
    {
        if (!is_array($this->project_name_patterns)) {
            $this->project_name_patterns = [];
        }

        if (!empty($this->project_name_patterns)) {
            usort($this->project_name_patterns, function ($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
        }

        if ($active !== null) {
            return array_filter($this->project_name_patterns, function ($item) use ($active) {
                return (bool)$item['active'] === (bool)$active;
            });
        }

        return $this->project_name_patterns;
    }

}
