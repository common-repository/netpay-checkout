<h3><?php _e( 'Cards', 'netpay' ); ?></h3>
<div id="netpay_card_panel">
	<table>
		<tr>
			<th><?php _e( 'Name', 'netpay' ); ?></th>
			<th><?php _e( 'Number', 'netpay' ); ?></th>
			<th><?php _e( 'Created date', 'netpay' ); ?></th>
			<th><?php _e( 'Action', 'netpay' ); ?></th>
		</tr>
		<tbody>
			<?php if ( isset( $viewData['existing_cards'] ) ): ?>
				<?php foreach( $viewData['existing_cards'] as $card ): ?>
					<?php
						$nonce = wp_create_nonce( 'netpay_delete_card_' . $card['id'] );
						$created_date = date_i18n( get_option( 'date_format' ), strtotime($card['created']));
					?>
					<tr>
						<td><?= $card['name'] ?></td>
						<td>XXXX XXXX XXXX <?= $card['last_digits'] ?></td>
						<td><?= $created_date ?></td>
						<td>
							<button
								class='button delete_card'
								data-card-id=<?= $card['id'] ?>
								data-delete-card-nonce=<?= $nonce ?>
							>
								<?php _e( 'Delete', 'netpay' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<h4><?php _e( 'Add new card', 'netpay' ); ?></h4>
	<form name="netpay_cc_form" id="netpay_cc_form">
		<?php wp_nonce_field('netpay_add_card','netpay_add_card_nonce'); ?>

		<?php if($viewData['secure_form_enabled']): ?>
			<div id="netpay-card" style="width:100%; max-width: 400px;"></div>
		<?php else: ?>
			<fieldset>
				<?php require_once( __DIR__ . '/../payment/form-creditcard.php' ); ?>
				<div class="clear"></div>
			</fieldset>
		<?php endif; ?>

	</form>
	<button id="netpay_add_new_card" class="button"><?php _e( 'Save card', 'netpay' ); ?></button>
</div>

<?php if($viewData['secure_form_enabled']): ?>
	<script>
		window.CARD_FORM_THEME = "<?php echo $viewData['cardFormTheme'] ?>";
		window.FORM_DESIGN = JSON.parse(`<?php echo json_encode($viewData['formDesign']) ?>`);
		window.CARD_BRAND_ICONS = JSON.parse(`<?php echo json_encode($viewData['cardIcons']) ?>`);
		window.LOCALE = `<?php echo get_locale(); ?>`;
		window.NETPAY_CUSTOM_FONT_OTHER = 'Other';
	</script>
<?php endif; ?>
