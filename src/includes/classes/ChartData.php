<?php
/*[pro exclude-file-from="lite"]*/
/**
 * Chart Data; for Stats.
 *
 * @since     141111 First documented version.
 *
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license   GNU General Public License, version 3
 */
namespace WebSharks\CommentMail\Pro;

/**
 * Chart Data; for Stats.
 *
 * @since 141111 First documented version.
 */
class ChartData extends AbsBase
{
    /**
     * @type string Input view.
     *
     * @since 141111 First documented version.
     */
    protected $input_view;

    /**
     * @type string Current view.
     *
     * @since 141111 First documented version.
     */
    protected $view;

    /**
     * @type \stdClass Chart specs.
     *
     * @since 141111 First documented version.
     */
    protected $chart;

    /**
     * @type array Any errors.
     *
     * @since 141111 First documented version.
     */
    protected $errors;

    /**
     * @type array Chart colors.
     *
     * @since 141111 First documented version.
     */
    protected $colors = [
        'fillColor'       => 'rgba(51, 158, 43, 1)',
        'strokeColor'     => 'rgba(25, 79, 22, 1)',
        'highlightFill'   => 'rgba(52, 96, 152, 1)',
        'highlightStroke' => 'rgba(23, 44, 72, 1)',
    ];

    /**
     * @type array Primary chart colors.
     *
     * @since 141111 First documented version.
     */
    protected $primary_colors = [
        'fillColor'       => 'rgba(52, 96, 152, .5)',
        'strokeColor'     => 'rgba(52, 96, 152, .7)',
        'highlightFill'   => 'rgba(52, 96, 152, .7)',
        'highlightStroke' => 'rgba(52, 96, 152, 1)',
    ];

    /**
     * @type array Secondary chart colors.
     *
     * @since 141111 First documented version.
     */
    protected $secondary_colors = [
        'fillColor'       => 'rgba(51, 158, 43, .5)',
        'strokeColor'     => 'rgba(51, 158, 43, .7)',
        'highlightFill'   => 'rgba(51, 158, 43, .7)',
        'highlightStroke' => 'rgba(51, 158, 43, 1)',
    ];

    /**
     * @type array Gradient chart colors.
     *
     * @since 141111 First documented version.
     */
    protected $gradient_colors = [
        '#4EAD47', '#307D27', '#5A85BE', '#346098',
    ];

    /**
     * Class constructor.
     *
     * @since 141111 First documented version.
     *
     * @param array $request_args Arguments to the constructor.
     *                            These should NOT be trusted; they come from a `$_REQUEST` action.
     *
     * @throws \exception If a security flag is triggered on `$this->data_file`.
     */
    public function __construct(array $request_args = [])
    {
        parent::__construct();

        $default_request_args = [
            'view' => '',

            'type'    => '',
            'post_id' => '',
            'exclude' => [],

            'from' => '',
            'to'   => '',

            'by' => '',
        ];
        $request_args = array_merge($default_request_args, $request_args);
        $request_args = array_intersect_key($request_args, $default_request_args);

        $this->input_view = $this->view = trim(strtolower((string) $request_args['view']));

        if ($this->input_view === 'subs_overview_by_post_id') {
            $this->view = 'subs_overview'; // Same handler.
        }
        if ($this->input_view === 'queued_notifications_overview_by_post_id') {
            $this->view = 'queued_notifications_overview'; // Same handler.
        }
        $ClassCase = function ($string) {
            $string = strtolower((string) $string);
            return ucfirst(preg_replace_callback('/_(.)/', function ($m) {
                return strtoupper($m[1]);
            }, $string));
        };
        $methodCase = function ($string) {
            $string = strtolower((string) $string);
            return lcfirst(preg_replace_callback('/_(.)/', function ($m) {
                return strtoupper($m[1]);
            }, $string));
        };
        $this->view = $ClassCase($this->view); // Force ClassCase.

        $this->chart = new \stdClass(); // Object properties.

        $this->chart->type    = $methodCase(trim((string) $request_args['type']));
        $this->chart->post_id = abs((integer) $request_args['post_id']);
        $this->chart->exclude = (array) $request_args['exclude'];

        $this->chart->from_time = $this->plugin->utils_string->trim((string) $request_args['from'], '', ',;');
        $this->chart->to_time   = $this->plugin->utils_string->trim((string) $request_args['to'], '', ',;');

        $this->chart->by = trim(strtolower((string) $request_args['by']));

        $this->errors = []; // Initialize.

        $this->maybeOutput();
    }

    /**
     * Chart data output; in JSON format.
     *
     * @since 141111 First documented version.
     */
    protected function maybeOutput()
    {
        if (!current_user_can($this->plugin->manage_cap)) {
            if (!current_user_can($this->plugin->cap)) {
                return; // Unauthenticated; ignore.
            }
        }
        if ($this->chartIsValid() && !$this->errors) {
            echo json_encode($this->{$this->view.'X'}());
        } elseif ($this->errors) { // Return `errors` property w/ markup.
            echo json_encode(['errors' => $this->errorsMarkup()]);
        }
    }

    /**
     * Chart data for a particular view.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data.
     */
    protected function subsOverviewX()
    {
        return $this->{__FUNCTION__.$this->chart->type}();
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewXEventSubscribedTotals()
    {
        return $this->subsOverviewEventSubscribedTotals();
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewXEventSubscribedMostPopularPosts()
    {
        return $this->subsOverviewEventSubscribedPostPopularity('most');
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewXEventSubscribedLeastPopularPosts()
    {
        return $this->subsOverviewEventSubscribedPostPopularity('least');
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for Google Visualization.
     */
    protected function subsOverviewXEventSubscribedAudienceByGeoCountry()
    {
        return $this->subsOverviewEventSubscribedAudienceByGeoCountry();
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for Google Visualization.
     */
    protected function subsOverviewXEventSubscribedAudienceByGeoUsRegion()
    {
        return $this->subsOverviewEventSubscribedAudienceByGeoRegion('US');
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for Google Visualization.
     */
    protected function subsOverviewXEventSubscribedAudienceByGeoCaRegion()
    {
        return $this->subsOverviewEventSubscribedAudienceByGeoRegion('CA');
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewXEventConfirmationPercentages()
    {
        return $this->subsOverviewEventStatusPercentages(['subscribed'], __('Confirmed', SLUG_TD));
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewXEventSuspensionPercentages()
    {
        return $this->subsOverviewEventStatusPercentages(['suspended'], __('Suspended', SLUG_TD));
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewXEventUnsubscribePercentages()
    {
        return $this->subsOverviewEventStatusPercentages(['trashed', 'deleted'], __('Unsubscribed', SLUG_TD));
    }

    /**
     * Chart data helper; for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @throws \exception If there is a query failure.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewEventSubscribedTotals()
    {
        $labels = $data = []; // Initialize.

        foreach ($this->chart->time_periods as $_time_period) {
            $labels[] = $_time_period['from_label'].' - '.$_time_period['to_label'];
        }
        unset($_time_period); // Housekeeping.

        foreach ($this->chart->time_periods as $_time_period) {
            $_new_sub_ids_sql = $this->newSubIdsSql($_time_period['from_time'], $_time_period['to_time']);

            $_sql = 'SELECT SQL_CALC_FOUND_ROWS `sub_id`'.// Calc enable.
                    ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'sub_event_log').'`'.

                    ' WHERE 1=1'.// Initialize where clause.

                    ' AND `sub_id` IN('.$_new_sub_ids_sql.')'.
                    " AND `status` IN('subscribed')".

                    (in_array('systematics', $this->chart->exclude, true)
                        ? " AND `user_initiated` > '0'" : '').// User-initiated only.

                    ' GROUP BY `sub_id`'.// Unique subs only.

                    ' LIMIT 1'; // Only need one to check.

            if ($this->plugin->utils_db->wp->query($_sql) === false) {
                throw new \exception('Query failure.');
            }
            $data[] = (integer) $this->plugin->utils_db->wp->get_var('SELECT FOUND_ROWS()');
        }
        unset($_time_period, $_oby_sub_ids, $_sql); // Housekeeping.

        return [
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    array_merge(
                        $this->colors,
                        [
                            'label' => __('Total Subscriptions', SLUG_TD),
                            'data'  => $data,
                        ]
                    ),
                ],
            ],
            'options' => [
                'scaleLabel' => '<%=value%>',

                'tooltipTemplate' => '<%=label%>: <%=value%> '.
                                     '<%if(parseInt(value) < 1 || parseInt(value) > 1){%>'.__('subscriptions', SLUG_TD).'<%}%>'.
                                     '<%if(parseInt(value) === 1){%>'.__('subscription', SLUG_TD).'<%}%>',
            ],
        ];
    }

    /**
     * Chart data helper; for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @throws \exception If there is a query failure.
     *
     * @return array An array of all chart data; for Google Visualization `geochart` map.
     */
    public function subsOverviewEventSubscribedAudienceByGeoCountry()
    {
        $data = [
            [ // Initialize column headers.
              __('Country', SLUG_TD),
              __('Total Subscriptions', SLUG_TD),
              __('Percentage', SLUG_TD),
            ],
        ];
        $grand_total     = 0; // Initialize.
        $new_sub_ids_sql = $this->newSubIdsSql($this->chart->from_time, $this->chart->to_time);

        $sql  // Counts country totals by distinct `sub_id`; ordered by popularity.

            = 'SELECT `country`, `sub_id`, COUNT(DISTINCT(`sub_id`)) AS `total_subs`'.
              ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'sub_event_log').'`'.

              ' WHERE 1=1'.// Initialize where clause.

              ' AND `sub_id` IN('.$new_sub_ids_sql.')'.
              " AND `status` IN('subscribed')".
              " AND `country` != ''".

              (in_array('systematics', $this->chart->exclude, true)
                  ? " AND `user_initiated` > '0'" : '').// User-initiated only.

              ' GROUP BY `country`'.// Unique countries only.

              ' ORDER BY `total_subs` DESC'.

              ' LIMIT 400'; // 400 max allowed by Google; should be plenty.

        if (($results = $this->plugin->utils_db->wp->get_results($sql))) {
            foreach (($results = $this->plugin->utils_db->typifyDeep($results)) as $_result) {
                $_result->total_subs = (integer) $_result->total_subs;
                $data[]              = [ // Adds a new data row for the table we are building.
                                         [
                                             'v' => strtoupper($_result->country), // Value and full name.
                                             'f' => $this->plugin->utils_map->countryName($_result->country),
                                         ],
                                         $_result->total_subs, '', // To be filled after.
                ];
                $grand_total += $_result->total_subs; // Keep a running total of all subscriptions.
            }
        }
        unset($_result, $_percent_of_total_subs); // Housekeeping.

        foreach ($data as $_key => &$_dataset) { // Add total & percentages into tooltips.
            $_dataset[2] = $this->plugin->utils_i18n->subscriptions($_dataset[1]).// e.g. X subscription(s).
                           ' ('.$this->plugin->utils_math->percent($_dataset[1], $grand_total, 0, true).')';
        }
        unset($_key, $_dataset); // Housekeeping.

        return [
            'data'    => $data,
            'options' => [
                'region'      => 'world',
                'displayMode' => 'regions',
                'width'       => '100%', 'height' => 'auto',
                'colors'      => $this->gradient_colors,
            ],
        ];
    }

    /**
     * Chart data helper; for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @param string $country Regions in which country?
     *
     * @throws \exception If there is a query failure.
     *
     * @return array An array of all chart data; for Google Visualization `geochart` map.
     */
    public function subsOverviewEventSubscribedAudienceByGeoRegion($country)
    {
        $country = trim(strtoupper((string) $country));

        if (!in_array($country, ['US', 'CA'], true)) {
            $country = 'US'; // Force a valid country.
        }
        $country_lc = strtolower($country);

        $data = [
            [ // Initialize column headers.
              __('Region', SLUG_TD),
              __('Total Subscriptions', SLUG_TD),
              __('Percentage', SLUG_TD),
            ],
        ];
        $grand_total     = 0; // Initialize.
        $new_sub_ids_sql = $this->newSubIdsSql($this->chart->from_time, $this->chart->to_time);
        $region_codes    = array_keys($this->plugin->utils_map->{'iso_3166_2_'.$country_lc}());

        $sql  // Counts country totals by distinct `sub_id`; ordered by popularity.

            = 'SELECT `country`, `region`, `sub_id`, COUNT(DISTINCT(`sub_id`)) AS `total_subs`'.
              ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'sub_event_log').'`'.

              ' WHERE 1=1'.// Initialize where clause.

              ' AND `sub_id` IN('.$new_sub_ids_sql.')'.
              " AND `status` IN('subscribed')".

              " AND `region` IN('".implode("','", array_map('esc_sql', $region_codes))."')".
              " AND `country` = '".esc_sql($country)."'".

              (in_array('systematics', $this->chart->exclude, true)
                  ? " AND `user_initiated` > '0'" : '').// User-initiated only.

              ' GROUP BY `country`, `region`'.// Unique regions.

              ' ORDER BY `total_subs` DESC'.

              ' LIMIT 400'; // 400 max allowed by Google; should be plenty.

        if (($results = $this->plugin->utils_db->wp->get_results($sql))) {
            foreach (($results = $this->plugin->utils_db->typifyDeep($results)) as $_result) {
                $_result->total_subs = (integer) $_result->total_subs;
                $data[]              = [ // Adds a new data row for the table we are building.
                                         [
                                             'v' => strtoupper($_result->country.'-'.$_result->region), // Value and full name.
                                             'f' => $this->plugin->utils_map->{$country_lc.'_region_name'}($_result->region).', '.$_result->country,
                                         ],
                                         $_result->total_subs, '', // To be filled after.
                ];
                $grand_total += $_result->total_subs; // Keep a running total of all subscriptions.
            }
        }
        unset($_result, $_percent_of_total_subs); // Housekeeping.

        foreach ($data as $_key => &$_dataset) { // Add total & percentages into tooltips.
            $_dataset[2] = $this->plugin->utils_i18n->subscriptions($_dataset[1]).// e.g. X subscription(s).
                           ' ('.$this->plugin->utils_math->percent($_dataset[1], $grand_total, 0, true).')';
        }
        unset($_key, $_dataset); // Housekeeping.

        return [
            'data'    => $data,
            'options' => [
                'region'      => $country,
                'resolution'  => 'provinces',
                'displayMode' => 'regions',
                'width'       => '100%', 'height' => 'auto',
                'colors'      => $this->gradient_colors,
            ],
        ];
    }

    /**
     * Chart data helper; for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @param array  $status Status (or statuses) we are looking for.
     * @param string $label  Label for this change percentage.
     *
     * @throws \exception If there is a query failure.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewEventStatusPercentages(array $status, $label)
    {
        $labels = $data1 = $data2 = $percent = []; // Initialize.

        foreach ($this->chart->time_periods as $_time_period) {
            $labels[] = $_time_period['from_label'].' - '.$_time_period['to_label'];
        }
        unset($_time_period); // Housekeeping.

        foreach ($this->chart->time_periods as $_time_period) {
            $_sql1 = $this->newSubIdsSql($_time_period['from_time'], $_time_period['to_time'], ['calc_enable' => true]);

            $_new_sub_ids_sql2 = $this->newSubIdsSql($_time_period['from_time'], $_time_period['to_time']);

            $_sql2 = 'SELECT SQL_CALC_FOUND_ROWS `sub_id`'.// Calc enable.
                     ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'sub_event_log').'`'.

                     ' WHERE 1=1'.// Initialize where clause.

                     ' AND `sub_id` IN('.$_new_sub_ids_sql2.')'.
                     " AND `status` IN('".implode("','", array_map('esc_sql', $status))."')".

                     (in_array('systematics', $this->chart->exclude, true)
                         ? " AND `user_initiated` > '0'" : '').// User-initiated only.

                     ' GROUP BY `sub_id`'.// Unique subs only.

                     ' LIMIT 1'; // Only need one to check.

            if ($this->plugin->utils_db->wp->query($_sql1) === false) {
                throw new \exception('Query failure.');
            }
            $data1[] = (integer) $this->plugin->utils_db->wp->get_var('SELECT FOUND_ROWS()');

            if ($this->plugin->utils_db->wp->query($_sql2) === false) {
                throw new \exception('Query failure.');
            }
            $data2[] = (integer) $this->plugin->utils_db->wp->get_var('SELECT FOUND_ROWS()');
        }
        unset($_time_period, $_sql1, $_new_sub_ids_sql2, $_sql2); // Housekeeping.

        foreach (array_keys($data2) as $_key) { // Calculate percentages.
            $percent[$_key] = $this->plugin->utils_math->percent($data2[$_key], $data1[$_key]);
        }
        unset($_key); // Housekeeping.

        return [
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    array_merge(
                        $this->secondary_colors,
                        [
                            'label' => __('New Subscriptions', SLUG_TD),
                            'data'  => $data1,
                        ]
                    ),
                    array_merge(
                        $this->primary_colors,
                        [
                            'label' => sprintf(__('Total %1$s', SLUG_TD), $label),
                            'data'  => $data2, 'percent' => $percent,
                        ]
                    ),
                ],
            ],
            'options' => [
                'scaleLabel' => '<%=value%>',

                'multiTooltipTemplate' => '<%=datasetLabel%>: <%=value%>'.
                                          '<%if(typeof percent === "number"){%> (<%=percent%>%)<%}%>',
            ],
        ];
    }

    /**
     * Chart data helper; for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @param string $popularity Popularity type; e.g. `most` or `least`.
     *
     * @throws \exception If there is a query failure.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function subsOverviewEventSubscribedPostPopularity($popularity)
    {
        $labels = $data = []; // Initialize.

        $new_sub_ids_sql = $this->newSubIdsSql($this->chart->from_time, $this->chart->to_time);

        $sql // Counts post totals by distinct `sub_id`; ordered by popularity.

            = 'SELECT `post_id`, `sub_id`, COUNT(DISTINCT(`sub_id`)) AS `total_subs`'.
              ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'sub_event_log').'`'.

              ' WHERE 1=1'.// Initialize where clause.

              ' AND `sub_id` IN('.$new_sub_ids_sql.')'.
              " AND `status` IN('subscribed')".

              (in_array('systematics', $this->chart->exclude, true)
                  ? " AND `user_initiated` > '0'" : '').// User-initiated only.

              ' GROUP BY `post_id`'.// Unique posts only.

              ' ORDER BY `total_subs` '.// Most or least?
              ($popularity === 'least' ? 'ASC' : 'DESC').

              ' LIMIT 25'; // 25 max.

        if (($results = $this->plugin->utils_db->wp->get_results($sql))) {
            foreach (($results = $this->plugin->utils_db->typifyDeep($results)) as $_result) {
                $_post            = get_post($_result->post_id);
                $_post_type       = $_post ? get_post_type_object($_post->post_type) : null;
                $_post_type       = $_post_type ? $_post_type->labels->singular_name : __('Post', SLUG_TD);
                $_post_title_clip = $_post && $_post->post_title ? ' — '.$this->plugin->utils_string->clip($_post->post_title, 20) : '';

                $labels[] = sprintf(__('%1$s ID #%2$s%3$s', SLUG_TD), $_post_type, $_result->post_id, $_post_title_clip);
                $data[]   = (integer) $_result->total_subs; // Total subscriptions.
            }
        }
        unset($_result, $_post, $_post_type, $_post_title_clip); // Housekeeping.

        if (empty($labels)) {
            $labels[] = '—'; // Must have something.
        }
        if (empty($data)) {
            $data[] = 0; // Must have something.
        }
        return [
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    array_merge(
                        $this->colors,
                        [
                            'label' => __('Total Subscriptions', SLUG_TD),
                            'data'  => $data,
                        ]
                    ),
                ],
            ],
            'options' => [
                'scaleLabel' => '<%=value%>',

                'tooltipTemplate' => '<%=label%>: <%=value%> '.
                                     '<%if(parseInt(value) < 1 || parseInt(value) > 1){%>'.__('subscriptions', SLUG_TD).'<%}%>'.
                                     '<%if(parseInt(value) === 1){%>'.__('subscription', SLUG_TD).'<%}%>',
            ],
        ];
    }

    /**
     * Sub-select SQL to acquire new sub IDs.
     *
     * @since 141111 First documented version.
     *
     * @param int   $from_time Time period from; UNIX timestamp.
     * @param int   $to_time   Time period to; UNIX timestamp.
     * @param array $args      Any additional behavioral args.
     *
     * @return string Sub-select SQL to acquire new sub IDs.
     */
    protected function newSubIdsSql($from_time, $to_time, array $args = [])
    {
        $from_time = (integer) $from_time;
        $to_time   = (integer) $to_time;

        $default_args = [
            'calc_enable'         => false,
            'check_post_id'       => true,
            'check_exclusions'    => true,
            'sub_select_optimize' => true,
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        $calc_enable         = (boolean) $args['calc_enable'];
        $check_post_id       = (boolean) $args['check_post_id'];
        $check_exclusions    = (boolean) $args['check_exclusions'];
        $sub_select_optimize = (boolean) $args['sub_select_optimize'];

        if ($calc_enable) {
            $sub_select_optimize = false; // Incompatible.
        }
        $oby_sub_ids_sql = $this->obySubIdsSql($from_time, $to_time);

        return // Sub IDs that were inserted during this timeframe.

            ($sub_select_optimize // Optimize?
                ? 'SELECT `sub_id` FROM (' : '').
            // ↑ See: <http://jas.xyz/1I52mVE>

            'SELECT'.($calc_enable ? ' SQL_CALC_FOUND_ROWS' : '').' `sub_id`'.
            ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'sub_event_log').'`'.

            ' WHERE 1=1'.// Initialize where clause.

            ($check_post_id && $this->chart->post_id // Specific post ID?
                ? " AND `post_id` = '".esc_sql($this->chart->post_id)."'" : '').

            " AND `event` IN('inserted')".// New insertions only.

            ($check_exclusions && in_array('systematics', $this->chart->exclude, true)
                ? " AND `user_initiated` > '0'" : '').// User-initiated only.

            " AND `time` BETWEEN '".esc_sql($from_time)."' AND '".esc_sql($to_time)."'".

            ' AND `sub_id` NOT IN('.$oby_sub_ids_sql.')'.// Exclude these.
            // See notes below regarding these overwritten exclusions.

            ' GROUP BY `sub_id`'.// Unique subs only (always).

            ($calc_enable  // Only need one to check?
                ? ' LIMIT 1' : '').

            ($sub_select_optimize // Optimizing?
                ? ') AS `sub_id`' : '');
    }

    /**
     * Sub-select SQL to acquire overwritten sub IDs.
     *
     * @since 141111 First documented version.
     *
     * @param int   $from_time Time period from; UNIX timestamp.
     * @param int   $to_time   Time period to; UNIX timestamp.
     * @param array $args      Any additional behavioral args.
     *
     * @return string Sub-select SQL to acquire overwritten sub IDs.
     *
     * @note  The reason for this sub-select is that we want to avoid counting duplicates
     *    where an event took place against two or more unique sub IDs, but where some of these
     *    sub IDs were overwritten by another; which really points to the same underlying subscription.
     *
     *    For instance, we might have sub IDs: `1`, `2`, `3`; where `2` was overwritten by `3` in the same timeframe.
     *    In a case such as this, there were really only two subscriptions. Sub ID `2` should be excluded in favor of `3`.
     *    This sub-select allows us to detect when that was the case, so that `2` can be excluded from the query.
     *
     *    However, we do want to include calculations where an overwrite might have taken place outside the current timeframe.
     *    For instance, if `2` was overwritten by `3`; but that occurred sometime after the timeframe that we're querying; we don't want to
     *    exclude `2` in such a scenario, because `2` did occur within that particular timeframe and we need to count it in that case.
     */
    protected function obySubIdsSql($from_time, $to_time, array $args = [])
    {
        $from_time = (integer) $from_time;
        $to_time   = (integer) $to_time;

        $default_args = [ // Default arguments.
                          'sub_select_optimize' => true,
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        $sub_select_optimize = (boolean) $args['sub_select_optimize'];

        return // Sub IDs that were overwritten during this timeframe.

            ($sub_select_optimize // Optimize?
                ? 'SELECT `sub_id` FROM (' : '').
            // ↑ See: <http://jas.xyz/1I52mVE>

            'SELECT `sub_id`'.// Need the sub IDs for sub-queries.
            ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'sub_event_log').'`'.

            ' WHERE 1=1'.// Initialize where clause.

            " AND `event` = 'overwritten' AND `oby_sub_id` > '0'".

            " AND `time` BETWEEN '".esc_sql($from_time)."' AND '".esc_sql($to_time)."'".

            ' GROUP BY `sub_id`'.// Unique subs only (always).

            ($sub_select_optimize // Optimizing?
                ? ') AS `sub_id`' : '');
    }

    /**
     * Chart data for a particular view.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data.
     */
    protected function queuedNotificationsOverviewX()
    {
        return $this->{__FUNCTION__.$this->chart->type}();
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function queuedNotificationsOverviewXEventProcessedTotals()
    {
        return $this->queuedNotificationsOverviewEventProcessedTotals();
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function queuedNotificationsOverviewXEventProcessedPercentages()
    {
        return $this->queuedNotificationsOverviewEventPercentages(['invalidated', 'notified'], 'Processed');
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function queuedNotificationsOverviewXEventNotifiedPercentages()
    {
        return $this->queuedNotificationsOverviewEventPercentages(['notified'], 'Notified');
    }

    /**
     * Chart data for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function queuedNotificationsOverviewXEventInvalidatedPercentages()
    {
        return $this->queuedNotificationsOverviewEventPercentages(['invalidated'], 'Invalidated');
    }

    /**
     * Chart data helper; for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @throws \exception If there is a query failure.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function queuedNotificationsOverviewEventProcessedTotals()
    {
        $labels = $data = []; // Initialize.

        foreach ($this->chart->time_periods as $_time_period) {
            $labels[] = $_time_period['from_label'].' - '.$_time_period['to_label'];
        }
        unset($_time_period); // Housekeeping.

        foreach ($this->chart->time_periods as $_time_period) {
            $_new_queue_ids_sql = $this->newQueueIdsSql($_time_period['from_time'], $_time_period['to_time']);

            $_sql = 'SELECT SQL_CALC_FOUND_ROWS `queue_id`'.// Calc enable.
                    ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'queue_event_log').'`'.

                    ' WHERE 1=1'.// Initialize where clause.

                    ' AND `queue_id` IN('.$_new_queue_ids_sql.')'.
                    " AND `event` IN('invalidated','notified')".

                    ' GROUP BY `queue_id`'.// Unique entries only.

                    ' LIMIT 1'; // Only need one to check.

            if ($this->plugin->utils_db->wp->query($_sql) === false) {
                throw new \exception('Query failure.');
            }
            $data[] = (integer) $this->plugin->utils_db->wp->get_var('SELECT FOUND_ROWS()');
        }
        unset($_time_period, $_oby_sub_ids, $_sql); // Housekeeping.

        return [
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    array_merge(
                        $this->colors,
                        [
                            'label' => __('Total Processed Notifications', SLUG_TD),
                            'data'  => $data,
                        ]
                    ),
                ],
            ],
            'options' => [
                'scaleLabel' => '<%=value%>',

                'tooltipTemplate' => '<%=label%>: <%=value%> '.
                                     '<%if(parseInt(value) < 1 || parseInt(value) > 1){%>'.__('notifications', SLUG_TD).'<%}%>'.
                                     '<%if(parseInt(value) === 1){%>'.__('notification', SLUG_TD).'<%}%>',
            ],
        ];
    }

    /**
     * Chart data helper; for a particular view type.
     *
     * @since 141111 First documented version.
     *
     * @param array  $event Event (or events) we are looking for.
     * @param string $label Label for this processing percentage.
     *
     * @throws \exception If there is a query failure.
     *
     * @return array An array of all chart data; for ChartJS.
     */
    protected function queuedNotificationsOverviewEventPercentages(array $event, $label)
    {
        $labels = $data1 = $data2 = $percent = []; // Initialize.

        foreach ($this->chart->time_periods as $_time_period) {
            $labels[] = $_time_period['from_label'].' - '.$_time_period['to_label'];
        }
        unset($_time_period); // Housekeeping.

        foreach ($this->chart->time_periods as $_time_period) {
            $_sql1 = $this->newQueueIdsSql($_time_period['from_time'], $_time_period['to_time'], ['calc_enable' => true]);

            $_new_queue_ids_sql2 = $this->newQueueIdsSql($_time_period['from_time'], $_time_period['to_time']);

            $_sql2 = 'SELECT SQL_CALC_FOUND_ROWS `queue_id`'.// Calc enable.
                     ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'queue_event_log').'`'.

                     ' WHERE 1=1'.// Initialize where clause.

                     ' AND `queue_id` IN('.$_new_queue_ids_sql2.')'.
                     " AND `event` IN('".implode("','", array_map('esc_sql', $event))."')".

                     ' GROUP BY `queue_id`'.// Unique entries only.

                     ' LIMIT 1'; // Only need one to check.

            if ($this->plugin->utils_db->wp->query($_sql1) === false) {
                throw new \exception('Query failure.');
            }
            $data1[] = (integer) $this->plugin->utils_db->wp->get_var('SELECT FOUND_ROWS()');

            if ($this->plugin->utils_db->wp->query($_sql2) === false) {
                throw new \exception('Query failure.');
            }
            $data2[] = (integer) $this->plugin->utils_db->wp->get_var('SELECT FOUND_ROWS()');
        }
        unset($_time_period, $_sql1, $_new_queue_ids_sql2, $_sql2); // Housekeeping.

        foreach (array_keys($data2) as $_key) { // Calculate percentages.
            $percent[$_key] = $this->plugin->utils_math->percent($data2[$_key], $data1[$_key]);
        }
        unset($_key); // Housekeeping.

        return [
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    array_merge(
                        $this->secondary_colors,
                        [
                            'label' => __('Queued Notifications', SLUG_TD),
                            'data'  => $data1,
                        ]
                    ),
                    array_merge(
                        $this->primary_colors,
                        [
                            'label' => sprintf(__('Total %1$s', SLUG_TD), $label),
                            'data'  => $data2, 'percent' => $percent,
                        ]
                    ),
                ],
            ],
            'options' => [
                'scaleLabel' => '<%=value%>',

                'multiTooltipTemplate' => '<%=datasetLabel%>: <%=value%>'.
                                          '<%if(typeof percent === "number"){%> (<%=percent%>%)<%}%>',
            ],
        ];
    }

    /**
     * Sub-select SQL to acquire new queue IDs.
     *
     * @since 141111 First documented version.
     *
     * @param int   $from_time Time period from; UNIX timestamp.
     * @param int   $to_time   Time period to; UNIX timestamp.
     * @param array $args      Any additional behavioral args.
     *
     * @return string Sub-select SQL to acquire new queue IDs.
     */
    protected function newQueueIdsSql($from_time, $to_time, array $args = [])
    {
        $from_time = (integer) $from_time;
        $to_time   = (integer) $to_time;

        $default_args = [
            'calc_enable'         => false,
            'check_post_id'       => true,
            'check_exclusions'    => true,
            'sub_select_optimize' => true,
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        $calc_enable         = (boolean) $args['calc_enable'];
        $check_post_id       = (boolean) $args['check_post_id'];
        $check_exclusions    = (boolean) $args['check_exclusions'];
        $sub_select_optimize = (boolean) $args['sub_select_optimize'];

        if ($calc_enable) {
            $sub_select_optimize = false; // Incompatible.
        }
        $dby_queue_ids_sql = $this->dbyQueueIdsSql($from_time, $to_time);

        return // Queue IDs that were processed during this timeframe.

            ($sub_select_optimize // Optimize?
                ? 'SELECT `queue_id` FROM (' : '').
            // ↑ See: <http://jas.xyz/1I52mVE>

            'SELECT'.($calc_enable ? ' SQL_CALC_FOUND_ROWS' : '').' `queue_id`'.
            ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'queue_event_log').'`'.

            ' WHERE 1=1'.// Initialize where clause.

            ($check_post_id && $this->chart->post_id // Specific post ID?
                ? " AND `post_id` = '".esc_sql($this->chart->post_id)."'" : '').

            " AND `time` BETWEEN '".esc_sql($from_time)."' AND '".esc_sql($to_time)."'".

            ' AND `queue_id` NOT IN('.$dby_queue_ids_sql.')'.// Exclude these.
            // See notes below regarding these overwritten exclusions.

            ' GROUP BY `queue_id`'.// Unique entries only (always).

            ($calc_enable  // Only need one to check?
                ? ' LIMIT 1' : '').

            ($sub_select_optimize // Optimizing?
                ? ') AS `queue_id`' : '');
    }

    /**
     * Sub-select SQL to acquire queue IDs digested by others.
     *
     * @since 141111 First documented version.
     *
     * @param int   $from_time Time period from; UNIX timestamp.
     * @param int   $to_time   Time period to; UNIX timestamp.
     * @param array $args      Any additional behavioral args.
     *
     * @return string Sub-select SQL to acquire queue IDs digested by others.
     *
     * @note  The reason for this sub-select is that we want to avoid counting duplicates
     *    where an event took place against two or more unique queue IDs, but where some of these
     *    queue IDs were digested by another; which really points to the same underlying notification.
     *
     *    For instance, we might have queue IDs: `1`, `2`, `3`; where `2` was digested by `3` in the same timeframe.
     *    In a case such as this, there were really only two notifications. Queue ID `2` should be excluded in favor of `3`.
     *    This sub-select allows us to detect when that was the case, so that `2` can be excluded from the query.
     *
     *    However, we do want to include calculations where an digest might have taken place outside the current timeframe.
     *    For instance, if `2` was digested by `3`; but that occurred sometime after the timeframe that we're querying; we don't want to
     *    exclude `2` in such a scenario, because `2` did occur within that particular timeframe and we need to count it in that case.
     */
    protected function dbyQueueIdsSql($from_time, $to_time, array $args = [])
    {
        $from_time = (integer) $from_time;
        $to_time   = (integer) $to_time;

        $default_args = [ // Default arguments.
                          'sub_select_optimize' => true,
        ];
        $args = array_merge($default_args, $args);
        $args = array_intersect_key($args, $default_args);

        $sub_select_optimize = (boolean) $args['sub_select_optimize'];

        return // Queue IDs that were digested by others during this timeframe.

            ($sub_select_optimize // Optimize?
                ? 'SELECT `queue_id` FROM (' : '').
            // ↑ See: <http://jas.xyz/1I52mVE>

            'SELECT `queue_id`'.// Need the queue IDs for sub-queries.
            ' FROM `'.esc_sql($this->plugin->utils_db->prefix().'queue_event_log').'`'.

            ' WHERE 1=1'.// Initialize where clause.

            " AND `dby_queue_id` > '0'".// Digested by another.

            " AND `time` BETWEEN '".esc_sql($from_time)."' AND '".esc_sql($to_time)."'".

            ' GROUP BY `queue_id`'.// Unique queue entries only (always).

            ($sub_select_optimize // Optimizing?
                ? ') AS `queue_id`' : '');
    }

    /**
     * Validates chart data.
     *
     * @since 141111 First documented version.
     *
     * @return bool `TRUE` if chart data validates.
     */
    protected function chartIsValid()
    {
        if (!$this->view || !method_exists($this, $this->view.'X')) {
            $this->errors[] = __('Invalid Chart View. Please try again.', SLUG_TD);
        }
        if (!method_exists($this, $this->view.'X'.$this->chart->type)) {
            $this->errors[] = __('Missing or invalid Chart Type. Please try again.', SLUG_TD);
        }
        if (preg_match('/(?:^geo|[a-z0-9]Geo)(?:[A-Z0-9]|$)/', $this->chart->type) && !$this->plugin->options['geo_location_tracking_enable']) {
            $this->errors[] = __('Geo IP tracking not enabled yet. Please check config. options.', SLUG_TD);
        }
        if (preg_match('/(?:^|_)by_post_id(?:_|$)/i', $this->input_view) && $this->chart->post_id <= 0) {
            $this->errors[] = __('Missing or invalid Post ID. Please try again.', SLUG_TD);
        }
        if (!$this->chart->from_time || !$this->chart->to_time) {
            $this->errors[] = __('Missing or invalid Date(s). Please try again.', SLUG_TD);
        }
        if (!in_array($this->chart->by, ['hours', 'days', 'weeks', 'months', 'years'], true)) {
            $this->errors[] = __('Missing or invalid Breakdown. Please try again.', SLUG_TD);
        }
        if (!$this->errors) {
            $this->parseTimesSetupPeriods(); // Times/periods.
        }
        if (!$this->errors) {
            // If no errors thus far, let's do one last on the times.

            if (!$this->chart->from_time || !$this->chart->to_time) {
                $this->errors[] = __('Missing or invalid Date(s). Please try again.', SLUG_TD);
            } elseif ($this->chart->from_time >= $this->chart->to_time) {
                $this->errors[] = __('From Date >= To Date. Please try again.', SLUG_TD);
            } elseif (empty($this->chart->time_periods)) {
                $this->errors[] = __('Not enough data for that time period and/or Breakdown. Please try again.', SLUG_TD);
            } elseif (count($this->chart->time_periods) > ($time_periods_max_limit = apply_filters(__CLASS__.'_time_periods_max_limit', 100))) {
                $this->errors[] = sprintf(__('Too many time periods needed. Please try again. Based on your configuration of this chart, there would need to be more than `%1$s` bars to represent the data that you want. This would require _many_ DB queries, and it would be very difficult to read the chart. Please broaden your Breakdown or reduce the difference between From Date and To Date.', SLUG_TD), $time_periods_max_limit);
            }
        }
        return empty($this->errors); // If no errors we're good-to-go!
    }

    /**
     * Chart time periods.
     *
     * @since 141111 First documented version.
     */
    protected function parseTimesSetupPeriods()
    {
        # Parse "from" time as a local timestamp.

        $local_relative_from_time_base = // GMT offset base.
            time() + (get_option('gmt_offset') * 3600);

        $this->chart->from_time = // Convert to timestamp; i.e. parse string.
            (integer) strtotime($this->chart->from_time, $local_relative_from_time_base);

        # Parse "to" time as a local timestamp.

        if ($this->chart->from_time) { // Only possible if we got a valid "from" time.
            $local_relative_to_time_base = // GMT offset base; with one exception for the word `now`.
                preg_match('/^now$/', $this->chart->to_time) ? time() + (get_option('gmt_offset') * 3600)
                    : $this->chart->from_time; // Else use current local "from" time as the base.
            $this->chart->to_time = (integer) strtotime($this->chart->to_time, $local_relative_to_time_base);
        } else {
            $this->chart->to_time = 0; // Cannot use this if the "from" time is incorrect.
        }
        # Invalid times before we even begin? e.g. One of the `strtotime()` calls choked above?

        if (!$this->chart->from_time || !$this->chart->to_time) {
            $this->errors[] = __('Missing or invalid Date(s). Please try again.', SLUG_TD);
        } elseif ($this->chart->from_time >= $this->chart->to_time) {
            $this->errors[] = __('From Date >= To Date. Please try again.', SLUG_TD);
        }
        if ($this->errors) {
            return; // Nothing more we can do here.
        }
        /* ---------------------------------------------------------- */

        # Begin date rounding; and also establish time period calculation variables.

        switch ($this->chart->by) {
            case 'hours': // Breakdown by hours?

                $by_seconds = 3600;
                $by_format  = 'M jS, Y @ g:i a';

                $this->chart->from_time = strtotime(date('Y-m-d H', $this->chart->from_time).':00');
                $this->chart->to_time   = strtotime(date('Y-m-d H', $this->chart->to_time).':59');

                $current_local_year = date('Y', time() + (get_option('gmt_offset') * 3600));
                if (date('Y', $this->chart->from_time) === $current_local_year) {
                    if (date('Y', $this->chart->to_time) === $current_local_year) {
                        $by_format = str_replace(', Y', '', $by_format);
                    }
                }
                break; // Break switch handler.

            case 'days': // Breakdown by days?

                $by_seconds = 86400;
                $by_format  = 'M jS, Y @ g:i a';

                $this->chart->from_time = strtotime(date('Y-m-d', $this->chart->from_time).' 00:00');
                $this->chart->to_time   = strtotime(date('Y-m-d', $this->chart->to_time).' 23:59');

                $current_local_year = date('Y', time() + (get_option('gmt_offset') * 3600));
                if (date('Y', $this->chart->from_time) === $current_local_year) {
                    if (date('Y', $this->chart->to_time) === $current_local_year) {
                        $by_format = str_replace(', Y', '', $by_format);
                    }
                }
                break; // Break switch handler.

            case 'weeks': // Breakdown by weeks?

                $by_seconds = 604800;
                $by_format  = 'D M jS, Y';

                if (strcasecmp(date('D', $this->chart->from_time), 'sun') === 0) {
                    $from_last_sunday = $this->chart->from_time;
                } else {
                    $from_last_sunday = strtotime('last Sunday', $this->chart->from_time);
                }
                $this->chart->from_time = strtotime(date('Y-m-d', $from_last_sunday).' 00:00');

                if (strcasecmp(date('D', $this->chart->to_time), 'sat') === 0) {
                    $to_next_saturday = $this->chart->to_time;
                } else {
                    $to_next_saturday = strtotime('next Saturday', $this->chart->to_time);
                }
                $this->chart->to_time = strtotime(date('Y-m-d', $to_next_saturday).' 23:59');

                $current_local_year = date('Y', time() + (get_option('gmt_offset') * 3600));
                if (date('Y', $this->chart->from_time) === $current_local_year) {
                    if (date('Y', $this->chart->to_time) === $current_local_year) {
                        $by_format = str_replace(', Y', '', $by_format);
                    }
                }
                break; // Break switch handler.

            case 'months': // Breakdown by months?

                $by_seconds = 2592000;
                $by_format  = 'M jS, Y';

                $this->chart->from_time = strtotime(date('Y-m', $this->chart->from_time).'-01 00:00');

                $to_month             = date('n', $this->chart->to_time);
                $to_year              = date('Y', $this->chart->to_time);
                $cal_days_in_to_month = cal_days_in_month(CAL_GREGORIAN, $to_month, $to_year);
                $cal_days_in_to_month = str_pad($cal_days_in_to_month, 2, '0', STR_PAD_LEFT);

                $this->chart->to_time = strtotime(date('Y-m', $this->chart->to_time).'-'.$cal_days_in_to_month.' 23:59');

                $current_local_year = date('Y', time() + (get_option('gmt_offset') * 3600));
                if (date('Y', $this->chart->from_time) === $current_local_year) {
                    if (date('Y', $this->chart->to_time) === $current_local_year) {
                        $by_format = str_replace(', Y', '', $by_format);
                    }
                }
                break; // Break switch handler.

            case 'years': // Breakdown by years?

                $by_seconds = 31536000;
                $by_format  = 'M Y';

                $this->chart->from_time = strtotime(date('Y', $this->chart->from_time).'-01-01 00:00');
                $this->chart->to_time   = strtotime(date('Y', $this->chart->to_time).'-12-31 23:59');

                break; // Break switch handler.

            default: // Unexpected breakdown "by" syntax?
                throw new \exception('Unexpected Breakdown.');
        }
        # Invalid times after adjustments/rounding above?

        if (!$this->chart->from_time || !$this->chart->to_time) {
            $this->errors[] = __('Missing or invalid Date(s). Please try again.', SLUG_TD);
        } elseif ($this->chart->from_time >= $this->chart->to_time) {
            $this->errors[] = __('From Date >= To Date. Please try again.', SLUG_TD);
        }
        if ($this->errors) {
            return; // Nothing more we can do here.
        }
        # Now let's convert the local times into UTC times.

        $this->chart->from_time -= get_option('gmt_offset') * 3600;
        $this->chart->to_time -= get_option('gmt_offset') * 3600;

        # Construct time periods based on "from" and "to" now.

        $_this            = $this; // Reference needed for this closure.
        $time_offset_bump = function ($time_offset) use ($_this, $by_seconds) {
            if ($by_seconds !== 2592000) {
                return $by_seconds;
            }
            $current_month             = date('n', $_this->chart->from_time + $time_offset);
            $current_year              = date('Y', $_this->chart->from_time + $time_offset);
            $cal_days_in_current_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);

            return $cal_days_in_current_month * 86400;
        };
        for ($_period = 0, $_time_offset = 0;
            $this->chart->from_time + $_time_offset + $time_offset_bump($_time_offset) <= $this->chart->to_time;
            $_period++, $_time_offset += $time_offset_bump($_time_offset)) {
            $this->chart->time_periods[$_period] = [
                'from_time'  => $this->chart->from_time + $_time_offset,
                'from_label' => $this->plugin->utils_date->i18n($by_format, $this->chart->from_time + $_time_offset),

                'to_time'  => $this->chart->from_time + $_time_offset + $time_offset_bump($_time_offset) - 1,
                'to_label' => $this->plugin->utils_date->i18n($by_format, $this->chart->from_time + $_time_offset + $time_offset_bump($_time_offset) - 1),
            ];
        }
        unset($_period, $_time_offset); // Housekeeping.
    }

    /**
     * Markup for display of errors.
     *
     * @since 141111 First documented version.
     *
     * @return string Markup for errors display.
     */
    protected function errorsMarkup()
    {
        $errors_html = // Convert all errors to HTML markup.
            array_map([$this->plugin->utils_string, 'markdownNoP'], $this->errors);

        return '<div class="pmp-note pmp-error" style="margin:1em 0 0 0;">'.

               ' <p style="margin:0 0 .5em 0; font-weight:bold;">'.
               '    <i class="fa fa-warning"></i> '.__('Please review the following error(s):', SLUG_TD).
               ' </p>'.

               ' <ul class="pmp-list-items" style="margin-top:0; margin-bottom:0;">'.
               '    <li>'.implode('</li><li>', $errors_html).'</li>'.
               ' </ul>'.

               '</div>';
    }
}
