<?php
/**
 * Provides HTML code for the options page.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/admin/partials
 */

?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form action="options.php" method="post">
	<?php
		// Output security fields for the settings page.
		settings_fields( $this->options_slug );

		// Output settings sections and fields.
		do_settings_sections( $this->options_slug );

		// Output save button.
		submit_button();
	?>
	</form>
</div>
