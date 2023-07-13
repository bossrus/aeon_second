<?php

class User
{

    // GENERAL

    public static function user_info($d)
    {
        // vars
        $user_id = isset($d[ 'user_id' ]) && is_numeric($d[ 'user_id' ]) ? $d[ 'user_id' ] : 0;
        $phone = isset($d[ 'phone' ]) ? preg_replace('~\D+~', '', $d[ 'phone' ]) : 0;
        // where
        if ($user_id) $where = "user_id='" . $user_id . "'";
        else if ($phone) $where = "phone='" . $phone . "'";
        else return [];
        // info
        $query = "SELECT plot_id, 
            first_name, 
            last_name, 
            email, 
            phone, 
            last_login, 
            user_id,
            access FROM users WHERE " . $where . " LIMIT 1;";
//        var_dump($query);
        $q = DB::query($query) or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'user_id' => (int)$row[ 'user_id' ],
                'access' => (int)$row[ 'access' ],
                'plot_id' => (int)$row[ 'plot_id' ],
                'first_name' => $row[ 'first_name' ],
                'last_name' => $row[ 'last_name' ],
                'email' => $row[ 'email' ],
                'phone' => $row[ 'phone' ],
            ];
        } else {
            return [
                'user_id' => 0,
                'access' => 0,
                'plot_id' => 0,
                'first_name' => 0,
                'last_name' => 0,
                'email' => 0,
                'phone' => 0,
            ];
        }
    }

    public static function user_edit_window($d = [])
    {
        $user_id = isset($d[ 'user_id' ]) && is_numeric($d[ 'user_id' ]) ? $d[ 'user_id' ] : 0;
        HTML::assign('user', User::user_info(['user_id' => $user_id]));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    public static function users_list($d = [])
    {
        // vars
        $search = isset($d[ 'search' ]) && trim($d[ 'search' ]) ? $d[ 'search' ] : '';
        $offset = isset($d[ 'offset' ]) && is_numeric($d[ 'offset' ]) ? $d[ 'offset' ] : 0;
        $limit = 20;
        $items = [];
        // where
        $where = [];
        if ($search) {
            $where[] = "phone LIKE '%" . $search . "%'";
            $where[] = "first_name LIKE '%" . $search . "%'";
            $where[] = "last_name LIKE '%" . $search . "%'";
            $where[] = "email LIKE '%" . $search . "%'";
        }
        $where = $where ? "WHERE " . implode(" OR ", $where) : "";
        // info
        $q = DB::query("SELECT plot_id, 
            first_name, 
            last_name, 
            email, 
            phone, 
            last_login, 
            user_id
            FROM users " . $where . "
             LIMIT " . $offset . ", " . $limit . ";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'plot_id' => $row[ 'plot_id' ],
                'first_name' => $row[ 'first_name' ],
                'last_name' => $row[ 'last_name' ],
                'email' => $row[ 'email' ],
                'phone' => $row[ 'phone' ],
                'last_login' => date('Y/m/d', $row[ 'last_login' ]),
                'user_id' => $row[ 'user_id' ],
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users " . $where . ";");
        $count = ($row = DB::fetch_row($q)) ? $row[ 'count(*)' ] : 0;
        $url = 'users';
        if ($search) $url .= '?search=' . $search . '&';
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }


    public static function users_list_plots($number)
    {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%" . $number . "%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row[ 'plot_id' ]);
            $val = false;
            foreach ($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int)$row[ 'user_id' ],
                'first_name' => $row[ 'first_name' ],
                'email' => $row[ 'email' ],
                'phone_str' => phone_formatting($row[ 'phone' ])
            ];
        }
        // output
        return $items;
    }

    public static function user_fetch($d = [])
    {
        $info = User::users_list($d);
        HTML::assign('users', $info[ 'items' ]);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info[ 'paginator' ]];
    }

    public static function user_edit_update($d = [])
    {
        // vars
        $plot_id = $d[ 'plot_id' ] ?? '';
        $first_name = $d[ 'first_name' ] ?? '';
        $last_name = $d[ 'last_name' ] ?? '';
        $email = $d[ 'email' ] ?? '';
        $phone = $d[ 'phone' ] ?? '';
        $user_id = isset($d[ 'user_id' ]) && ($d[ 'user_id' ] != 0) ? $d[ 'user_id' ] : null;
        $offset = $d[ 'offset' ] ?? 0;

        $set = [];
        $set[] = "plot_id='" . $plot_id . "'";
        $set[] = "first_name='" . $first_name . "'";
        $set[] = "last_name='" . $last_name . "'";
        $set[] = "email='" . (strtolower($email)) . "'";
        $set[] = "phone='" . $phone . "'";
        $set = implode(", ", $set);
        // update
        if ($user_id) {
            DB::query("UPDATE users SET " . $set . " WHERE user_id='" . $user_id . "' LIMIT 1;") or die (DB::error());
        } else {
            DB::query("INSERT INTO users SET " . $set . ";") or die (DB::error());
        }
        // output
        return User::user_fetch(['offset' => $offset]);
    }

    public static function user_delete($d = [])
    {
        // vars
        $user_id = isset($d[ 'user_id' ]) && ($d[ 'user_id' ] != 0) ? $d[ 'user_id' ] : null;
        $offset = $d[ 'offset' ] ?? 0;

        // update
        if ($user_id) {
            DB::query("DELETE FROM users WHERE user_id='" . $user_id . "';") or die (DB::error());
        }
        // output
        return User::user_fetch(['offset' => $offset]);
    }


}
