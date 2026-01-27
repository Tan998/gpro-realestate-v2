
// how to use , copy to function.php and run http://localhost/gpro-realestate/wp-admin/?run_import=prefecture


add_action('admin_init', function () {

    if (!current_user_can('administrator')) {
        return;
    }

    if (!isset($_GET['run_import']) || $_GET['run_import'] !== 'prefecture') {
        return;
    }

    $csv = WP_CONTENT_DIR . '/uploads/prefectures_no_bom.csv';
    $taxonomy = 'property_county_state';

    if (!file_exists($csv)) {
        wp_die('CSV not found');
    }

    $handle = fopen($csv, 'r');
    fgetcsv($handle); // skip header

    echo '<pre>';

    while (($row = fgetcsv($handle)) !== false) {

        if (count($row) < 4) continue;

        [$id, $pref_id, $en, $ja] = $row;

        $slug = sanitize_title($en);

        $existing = term_exists($ja, $taxonomy);

        if (!$existing) {

            $term = wp_insert_term($ja, $taxonomy, [
                'slug' => $slug
            ]);

            if (is_wp_error($term)) {
                echo "ERROR: {$ja} → " . $term->get_error_message() . "\n";
                continue;
            }

            add_term_meta($term['term_id'], 'prefecture_id', (int)$pref_id, true);
            add_term_meta($term['term_id'], 'prefecture_en', $en, true);

            echo "IMPORTED: {$ja}\n";

        } else {
            echo "EXISTS: {$ja}\n";
        }
    }

    fclose($handle);
    echo "\nDONE";
    exit;
});


//import citis of japan
//// how to use , copy to function.php and run http://localhost/gpro-realestate/wp-admin/?run_import=cities

add_action('admin_init', function () {

    // chỉ admin
    if (!current_user_can('administrator')) {
        return;
    }

    // chỉ chạy khi có param
    if (!isset($_GET['run_import']) || $_GET['run_import'] !== 'cities') {
        return;
    }

    $csv = WP_CONTENT_DIR . '/uploads/cities_in_japan_2024.csv';
    $taxonomy = 'property_city';

    if (!file_exists($csv)) {
        wp_die('CSV not found: ' . $csv);
    }

    $handle = fopen($csv, 'r');
    if (!$handle) {
        wp_die('Cannot open CSV');
    }

    // skip header
    fgetcsv($handle);

    echo '<pre>';

    // cache: prefecture_id + city_ja => term_id
    $city_cache = [];

    while (($row = fgetcsv($handle)) !== false) {

        if (count($row) < 5) {
            continue;
        }

        [$id, $pref_id, $city_en, $city_ja, $ward_ja] = $row;

        // bỏ dòng rỗng
        if (empty($city_ja)) {
            continue;
        }

        // key để tránh trùng city cùng tên ở prefecture khác
        $city_key = $pref_id . '_' . $city_ja;

        /**
         * 1️⃣ CREATE / GET CITY (市 / 町 / 村)
         */
        if (!isset($city_cache[$city_key])) {

            $existing_city = term_exists($city_ja, $taxonomy);

            if (!$existing_city) {

                $city_term = wp_insert_term($city_ja, $taxonomy, [
                    'slug' => sanitize_title($city_en),
                ]);

                if (is_wp_error($city_term)) {
                    echo "❌ CITY ERROR {$city_ja}: " . $city_term->get_error_message() . "\n";
                    continue;
                }

                $city_id = $city_term['term_id'];

                // gắn prefecture_id
                add_term_meta($city_id, 'prefecture_id', (int)$pref_id, true);

                echo "✔ CITY: {$city_ja}\n";

            } else {
                // term_exists có thể trả array hoặc int
                $city_id = is_array($existing_city)
                    ? $existing_city['term_id']
                    : $existing_city;
            }

            $city_cache[$city_key] = $city_id;
        }

        /**
         * 2️⃣ CREATE WARD (区) AS CHILD
         */
        if (!empty($ward_ja)) {

            $existing_ward = term_exists($ward_ja, $taxonomy);

            if (!$existing_ward) {

                $ward_term = wp_insert_term($ward_ja, $taxonomy, [
                    'parent' => $city_cache[$city_key],
                ]);

                if (!is_wp_error($ward_term)) {
                    echo "   └ WARD: {$ward_ja}\n";
                }
            }
        }
    }

    fclose($handle);

    echo "\nDONE CITIES IMPORT";
    exit;
});

