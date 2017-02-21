<?php
/**
 * Settings View
 *
 * @package Premise Social Media Blogger
 */

?>
<div class="wrap">

	<h2><?php esc_html_e( 'Premise Social Media Blogger Settings', 'psmb' ); ?></h2>

	<?php // Check if Wordpress version >= 4.0! (Instagram URL embed).
	if ( ! version_compare( get_bloginfo( 'version' ), '4.0', '>=' ) ) {

		$this->notification(
			__( 'Error: Please upgrade to WordPress 4 or higher!', 'psmb' ),
			'error'
		);

		wp_die();

	} ?>

	<form method="post" action="options.php" enctype="multipart/form-data" id="psmb-option-form" class="premise-admin">

	<?php
		submit_button( __( 'Save Settings', 'psmb' ), 'button button-primary right' );

		// This prints out all hidden setting fields.
		settings_fields( $this->options_group );
		do_settings_sections( $this->options_group );

		// Prevent float issues.
		echo '<div class="premise-clear"></div>';

		ob_start();

		$this->youtube->youtube_settings();

		$youtube_settings_content = ob_get_clean();

		ob_start();

		$this->instagram->instagram_settings();

		$instagram_settings_content = ob_get_clean();

		$tabs = array(
			// Load YouTube settings.
			array(
				'title' => __( 'YouTube', 'psmp' ),
				'icon' => 'fa-youtube-play',
				'content' => $youtube_settings_content,
			),
			// Load Instagram settings.
			array(
				'title' => __( 'Instagram', 'psmp' ),
				'icon' => 'fa-instagram',
				'content' => $instagram_settings_content,
			),
		);

		new Premise_Tabs( $tabs, 'wp-theme-options' );
	?>
	</form>
</div>
