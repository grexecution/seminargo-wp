<?php
/**
 * Template Name: Team Page
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main team-page">

        <!-- Team Header Section -->
        <section class="team-header">
            <div class="container">
                <h1 class="team-page-title">Unser seminargo Team für Sie im Einsatz</h1>
            </div>
        </section>

        <!-- Team Members Section -->
        <section class="team-members-section">
            <div class="container">

                <?php
                // Define exact team order - case-insensitive
                $team_order = array(
                    'ceo',
                    'key account',
                    'sales',
                    'accounting',
                    'meeting planner',
                    'innovation',
                    'marketing',
                    'online marketing',
                    'other'
                );

                // Query all team members
                $team_query = new WP_Query(array(
                    'post_type' => 'team',
                    'posts_per_page' => -1,
                    'orderby' => 'menu_order title',
                    'order' => 'ASC'
                ));

                if ($team_query->have_posts()) :
                    // Group team members by the 'team' custom field
                    $team_groups = array();
                    $debug_teams = array(); // For debugging

                    while ($team_query->have_posts()) : $team_query->the_post();
                        // Get the 'team' custom field - this determines which group
                        $team_field = get_post_meta(get_the_ID(), 'team', true);
                        // Get the 'position' custom field - this is just displayed text
                        $position_field = get_post_meta(get_the_ID(), 'position', true);

                        // Debug: collect all team values
                        $debug_teams[] = array(
                            'name' => get_the_title(),
                            'team_raw' => $team_field,
                            'position' => $position_field
                        );

                        // Normalize team field to lowercase and trim
                        $team_normalized = strtolower(trim($team_field));

                        // Default to 'other' if no team field is set
                        if (empty($team_normalized)) {
                            $team_normalized = 'other';
                        }

                        // Create group if doesn't exist
                        if (!isset($team_groups[$team_normalized])) {
                            $team_groups[$team_normalized] = array();
                        }

                        // Add member to their team group
                        $team_groups[$team_normalized][] = array(
                            'id' => get_the_ID(),
                            'name' => get_the_title(),
                            'position' => $position_field, // Just for display
                            'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium')
                        );
                    endwhile;

                    // Debug output (comment out after fixing)
                    if (current_user_can('administrator')) :
                        echo '<!-- DEBUG: Team field values found:';
                        echo "\n";
                        foreach ($debug_teams as $dt) {
                            echo sprintf('Name: %s | Team: "%s" | Position: "%s"',
                                $dt['name'],
                                $dt['team_raw'],
                                $dt['position']
                            );
                            echo "\n";
                        }
                        echo '-->';
                        echo '<!-- DEBUG: Groups created: ' . implode(', ', array_keys($team_groups)) . ' -->';
                    endif;

                    // Team labels for display
                    $team_labels = array(
                        'ceo' => 'CEO',
                        'key account' => 'Key Account Manager',
                        'sales' => 'Sales',
                        'accounting' => 'Accounting',
                        'meeting planner' => 'Meeting Planner',
                        'innovation' => 'Innovation',
                        'marketing' => 'Marketing',
                        'online marketing' => 'Online Marketing',
                        'other' => 'Team'
                    );

                    // Display teams in the specified order
                    $first_team = true;
                    foreach ($team_order as $team_key) :
                        if (isset($team_groups[$team_key]) && !empty($team_groups[$team_key])) :
                            ?>
                            <div class="team-group">
                                <?php if (!$first_team) : ?>
                                    <div class="team-divider">
                                        <span class="team-divider-label"><?php echo esc_html($team_labels[$team_key]); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="team-members-grid">
                                    <?php foreach ($team_groups[$team_key] as $member) : ?>
                                        <div class="team-member">
                                            <div class="team-member-image">
                                                <?php if ($member['image']) : ?>
                                                    <img src="<?php echo esc_url($member['image']); ?>" alt="<?php echo esc_attr($member['name']); ?>">
                                                <?php else : ?>
                                                    <div class="team-member-placeholder"></div>
                                                <?php endif; ?>
                                            </div>
                                            <h3 class="team-member-name"><?php echo esc_html($member['name']); ?></h3>
                                            <p class="team-member-position"><?php echo esc_html($member['position']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php
                            $first_team = false;
                        endif;
                    endforeach;

                    wp_reset_postdata();
                else :
                    ?>
                    <p class="no-team-members">Keine Teammitglieder gefunden.</p>
                    <?php
                endif;
                ?>

            </div>
        </section>

    </main>
</div>

<?php get_footer(); ?>
