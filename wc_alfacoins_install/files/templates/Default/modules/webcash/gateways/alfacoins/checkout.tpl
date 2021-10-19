{{ addon_settings_link|raw }}

<h5>{{ gateway_header }}</h5>

<div class="sepH_b">
	__('Инвойс №') {{ invoice_id }}
</div>

<div class="sepH_b">
	{% if email %} <b>__('Е-майл: ')</b> {{ email }} {% endif %}
</div>

<form id="pay" name="pay" class="webcash_ajax_form">
	<input type="hidden" name="action" value="checkout">
	<input type="hidden" name="gw_alias" value="alfacoins" />
	<input type="hidden" name="user_hash" value="{{ user_hash }}" />
	<input type="hidden" name="invoice_id" value="{{ invoice_id }}">
	<input type="hidden" name="email" value="{{ email }}">
	
	<button type="submit">__('Далее')</button>
</form>