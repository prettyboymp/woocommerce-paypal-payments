<?php
/**
 * Renders the settings of the Gateways.
 *
 * @package WooCommerce\PayPalCommerce\WcGateway\Settings
 */

declare(strict_types=1);

namespace WooCommerce\PayPalCommerce\WcGateway\Settings;

use WooCommerce\PayPalCommerce\ApiClient\Helper\DccApplies;
use WooCommerce\PayPalCommerce\Button\Helper\MessagesApply;
use WooCommerce\PayPalCommerce\Onboarding\State;
use WooCommerce\PayPalCommerce\WcGateway\Gateway\CreditCardGateway;
use Psr\Container\ContainerInterface;
use Woocommerce\PayPalCommerce\WcGateway\Helper\DccProductStatus;

/**
 * Class SettingsRenderer
 */
class SettingsRenderer {

	/**
	 * The settings.
	 *
	 * @var ContainerInterface
	 */
	private $settings;

	/**
	 * The current onboarding state.
	 *
	 * @var State
	 */
	private $state;

	/**
	 * The setting fields.
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Helper to see if DCC gateway can be shown.
	 *
	 * @var DccApplies
	 */
	private $dcc_applies;

	/**
	 * Helper to see if messages are supposed to show up.
	 *
	 * @var MessagesApply
	 */
	private $messages_apply;

	/**
	 * The DCC Product Status.
	 *
	 * @var DccProductStatus
	 */
	private $dcc_product_status;

	/**
	 * SettingsRenderer constructor.
	 *
	 * @param ContainerInterface $settings The Settings.
	 * @param State              $state The current state.
	 * @param array              $fields The setting fields.
	 * @param DccApplies         $dcc_applies Whether DCC gateway can be shown.
	 * @param MessagesApply      $messages_apply Whether messages can be shown.
	 * @param DccProductStatus   $dcc_product_status The product status.
	 */
	public function __construct(
		ContainerInterface $settings,
		State $state,
		array $fields,
		DccApplies $dcc_applies,
		MessagesApply $messages_apply,
		DccProductStatus $dcc_product_status
	) {

		$this->settings           = $settings;
		$this->state              = $state;
		$this->fields             = $fields;
		$this->dcc_applies        = $dcc_applies;
		$this->messages_apply     = $messages_apply;
		$this->dcc_product_status = $dcc_product_status;
	}

	/**
	 * Renders the multiselect field.
	 *
	 * @param string $field The current field HTML.
	 * @param string $key   The current key.
	 * @param array  $config The configuration array.
	 * @param string $value The current value.
	 *
	 * @return string
	 */
	public function render_multiselect( $field, $key, $config, $value ): string {

		if ( 'ppcp-multiselect' !== $config['type'] ) {
			return $field;
		}

		$options = array();
		foreach ( $config['options'] as $option_key => $option_value ) {
			$selected = ( in_array( $option_key, $value, true ) ) ? 'selected="selected"' : '';

			$options[] = '<option value="' . esc_attr( $option_key ) . '" ' . $selected . '>' .
			esc_html( $option_value ) .
			'</option>';
		}

		$html = sprintf(
			'<select
                        multiple
                         class="%s"
                         name="%s"
                     >%s</select>',
			esc_attr( implode( ' ', $config['class'] ) ),
			esc_attr( $key ) . '[]',
			implode( '', $options )
		);

		return $html;
	}

	/**
	 * Renders the password input field.
	 *
	 * @param string $field The current field HTML.
	 * @param string $key   The current key.
	 * @param array  $config The configuration array.
	 * @param string $value The current value.
	 *
	 * @return string
	 */
	public function render_password( $field, $key, $config, $value ): string {

		if ( 'ppcp-password' !== $config['type'] ) {
			return $field;
		}

		$html = sprintf(
			'<input
                        type="password"
                        autocomplete="new-password"
                        class="%s"
                        name="%s"
                        value="%s"
                     >',
			esc_attr( implode( ' ', $config['class'] ) ),
			esc_attr( $key ),
			esc_attr( $value )
		);

		return $html;
	}


	/**
	 * Renders the text input field.
	 *
	 * @param string $field The current field HTML.
	 * @param string $key   The current key.
	 * @param array  $config The configuration array.
	 * @param string $value The current value.
	 *
	 * @return string
	 */
	public function render_text_input( $field, $key, $config, $value ): string {

		if ( 'ppcp-text-input' !== $config['type'] ) {
			return $field;
		}

		$html = sprintf(
			'<input
                        type="text"
                        autocomplete="off"
                        class="%s"
                        name="%s"
                        value="%s"
                     >',
			esc_attr( implode( ' ', $config['class'] ) ),
			esc_attr( $key ),
			esc_attr( $value )
		);

		return $html;
	}

	/**
	 * Renders the heading field.
	 *
	 * @param string $field The current field HTML.
	 * @param string $key   The current key.
	 * @param array  $config The configuration array.
	 * @param string $value The current value.
	 *
	 * @return string
	 */
	public function render_heading( $field, $key, $config, $value ): string {

		if ( 'ppcp-heading' !== $config['type'] ) {
			return $field;
		}

		$html = sprintf(
			'<h3 class="%s">%s</h3>',
			esc_attr( implode( ' ', $config['class'] ) ),
			esc_html( $config['heading'] )
		);

		return $html;
	}

	/**
	 * Renders the settings.
	 */
	public function render() {

		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['ppcp-onboarding-error'] ) ) :
			?>
			<div class="notice notice-error">
				<p><strong>
				<?php
					esc_html_e(
						'We could not properly fetch the necessary credentials. Please try again.',
						'paypal-payments-for-woocommerce'
					);
				?>
				</strong></p>
			</div>
			<?php
		endif;
	    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_dcc = isset( $_GET[ SectionsRenderer::KEY ] ) && CreditCardGateway::ID === sanitize_text_field( wp_unslash( $_GET[ SectionsRenderer::KEY ] ) );
		$nonce  = wp_create_nonce( SettingsListener::NONCE );
		?>
		<input type="hidden" name="ppcp-nonce" value="<?php echo esc_attr( $nonce ); ?>">
		<?php
		foreach ( $this->fields as $field => $config ) :
			if ( ! in_array( $this->state->current_state(), $config['screens'], true ) ) {
				continue;
			}
			if ( $is_dcc && ! in_array( $config['gateway'], array( 'all', 'dcc' ), true ) ) {
				continue;
			}
			if ( ! $is_dcc && ! in_array( $config['gateway'], array( 'all', 'paypal' ), true ) ) {
				continue;
			}
			if (
				in_array( 'dcc', $config['requirements'], true )
				&& ! $this->dcc_applies->for_country_currency()
			) {
				continue;
			}
			if (
				in_array( 'dcc', $config['requirements'], true )
				&& ! $this->dcc_product_status->dcc_is_active()
			) {
				continue;
			}
			if (
				in_array( 'messages', $config['requirements'], true )
				&& ! $this->messages_apply->for_country()
			) {
				continue;
			}
			$value        = $this->settings->has( $field ) ? $this->settings->get( $field ) : null;
			$key          = 'ppcp[' . $field . ']';
			$id           = 'ppcp-' . $field;
			$config['id'] = $id;
			$th_td        = 'ppcp-heading' !== $config['type'] ? 'td' : 'th';
			$colspan      = 'ppcp-heading' !== $config['type'] ? 1 : 2;
			$classes      = isset( $config['classes'] ) ? $config['classes'] : array();
			?>
		<tr valign="top" id="<?php echo esc_attr( 'field-' . $field ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php if ( 'ppcp-heading' !== $config['type'] ) : ?>
			<th>
				<label
					for="<?php echo esc_attr( $id ); ?>"
				><?php echo esc_html( $config['title'] ); ?></label>
				<?php if ( isset( $config['desc_tip'] ) && $config['desc_tip'] ) : ?>
				<span
						class="woocommerce-help-tip"
						data-tip="<?php echo esc_attr( $config['description'] ); ?>"
				></span>
					<?php
					unset( $config['description'] );
				endif;
				?>
			</th>
			<?php endif; ?>
			<<?php echo esc_attr( $th_td ); ?> colspan="<?php echo (int) $colspan; ?>">
						<?php
						'ppcp-text' === $config['type'] ?
						$this->render_text( $config )
						: woocommerce_form_field( $key, $config, $value );
						?>
			</<?php echo esc_attr( $th_td ); ?>>
		</tr>
			<?php
		endforeach;

		if ( $is_dcc ) {
			if ( $this->dcc_applies->for_country_currency() ) {
				if ( State::STATE_ONBOARDED > $this->state->current_state() ) {
					$this->render_dcc_onboarding_info();
				} elseif ( State::STATE_ONBOARDED === $this->state->current_state() && $this->dcc_product_status->dcc_is_active() ) {
					$this->render_3d_secure_info();
				} elseif ( ! $this->dcc_product_status->dcc_is_active() ) {
					$this->render_dcc_not_active_yet();
				}
			} else {
				$this->render_dcc_does_not_apply_info();
			}
		}
	}

	/**
	 * Renders the ppcp-text field given a configuration.
	 *
	 * @param array $config The configuration array.
	 */
	private function render_text( array $config ) {
		echo wp_kses_post( $config['text'] );
		if ( isset( $config['hidden'] ) ) {
			$value = $this->settings->has( $config['hidden'] ) ?
				(string) $this->settings->get( $config['hidden'] )
				: '';
			echo ' <input
                    type = "hidden"
                    name = "ppcp[' . esc_attr( $config['hidden'] ) . ']"
                    value = "' . esc_attr( $value ) . '"
                    > ';
		}
	}

	/**
	 * Renders the information that the PayPal account can not yet process DCC.
	 */
	private function render_dcc_not_active_yet() {
		?>
		<tr>
			<th><?php esc_html_e( 'Onboarding', 'paypal-payments-for-woocommerce' ); ?></th>
			<td class="notice notice-error">
				<p>
					<?php
					esc_html_e(
						'Credit Card processing for your account has not yet been activated by PayPal. If your account is new, this can take some days. Otherwise, please get in contact with PayPal.',
						'paypal-payments-for-woocommerce'
					);
					?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the 3d secure info text.
	 */
	private function render_3d_secure_info() {
		?>
<tr>
	<th><?php esc_html_e( '3D Secure', 'paypal-payments-for-woocommerce' ); ?></th>
	<td>
		<p>
			<?php
			/**
			 * We still need to provide a docs link.
			 *
			 * @todo: Provide link to documentation.
			 */
			echo wp_kses_post(
				sprintf(
				// translators: %1$s and %2$s is a link tag.
					__(
						'3D Secure benefits cardholders and merchants by providing
                                  an additional layer of verification using Verified by Visa,
                                  MasterCard SecureCode and American Express SafeKey.
                                  %1$sLearn more about 3D Secure.%2$s',
						'paypal-payments-for-woocommerce'
					),
					'<a href = "#">',
					'</a>'
				)
			);
			?>
		</p>
	</td>
</tr>
		<?php
	}

	/**
	 * Renders the DCC onboarding info.
	 */
	private function render_dcc_onboarding_info() {
		?>
<tr>
	<th><?php esc_html_e( 'Onboarding', 'paypal-payments-for-woocommerce' ); ?></th>
<td class="notice notice-error">
	<p>
		<?php
			esc_html_e(
				'You need to complete your onboarding, before you can use the PayPal Card Processing option.',
				'paypal-payments-for-woocommerce'
			);
		?>

		<a
			href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=ppcp-gateway' ) ); ?>"
			>
			<?php esc_html_e( 'Click here to complete your onboarding.', 'paypal-payments-for-woocommerce' ); ?>
		</a>
	</p>
</td>
</tr>
		<?php
	}

	/**
	 * Renders the info, that DCC is not available in the merchant's country.
	 */
	private function render_dcc_does_not_apply_info() {
		?>
		<tr>
			<th><?php esc_html_e( 'Card Processing not available', 'paypal-payments-for-woocommerce' ); ?></th>
			<td class="notice notice-error">
				<p>
					<?php
					esc_html_e(
						'Unfortunatly, the card processing option is not yet available in your country.',
						'paypal-payments-for-woocommerce'
					);
					?>

					<a
							href="https://developer.paypal.com/docs/platforms/checkout/reference/country-availability-advanced-cards/"
							target="_blank"
							rel="noreferrer noopener"
					>
						<?php
						esc_html_e(
							'Click here to see, in which countries this option is currently available.',
							'paypal-payments-for-woocommerce'
						);
						?>
					</a>
				</p>
			</td>
		</tr>
		<?php
	}
}
