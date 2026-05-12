(function ($) {
	'use strict';

	function openModal(content) {
		$('#parag-mail-inspector-modal-content').html(content);
		$('#parag-mail-inspector-preview-modal').addClass('is-open').attr('aria-hidden', 'false');
	}

	function closeModal() {
		$('#parag-mail-inspector-preview-modal').removeClass('is-open').attr('aria-hidden', 'true');
		$('#parag-mail-inspector-modal-content').empty();
	}

	$(document).on('click', '.parag-mail-inspector-view-log', function () {
		var logId = $(this).data('log-id');

		openModal('<p>' + SimpleMailLoggerAdmin.i18n.loading + '</p>');

		$.post(SimpleMailLoggerAdmin.ajaxUrl, {
			action: 'parag_mail_inspector_get_log',
			nonce: SimpleMailLoggerAdmin.nonce,
			log_id: logId
		}).done(function (response) {
			if (response && response.success && response.data.html) {
				openModal(response.data.html);
			} else {
				openModal('<p>' + SimpleMailLoggerAdmin.i18n.error + '</p>');
			}
		}).fail(function () {
			openModal('<p>' + SimpleMailLoggerAdmin.i18n.error + '</p>');
		});
	});

	$(document).on('click', '[data-parag-mail-inspector-close]', closeModal);

	$(document).on('click', '#parag-mail-inspector-preview-modal', function (event) {
		if (event.target === this) {
			closeModal();
		}
	});

	$(document).on('keydown', function (event) {
		if (event.key === 'Escape') {
			closeModal();
		}
	});

	$(document).on('click', '.parag-mail-inspector-delete-link', function (event) {
		if (!window.confirm(SimpleMailLoggerAdmin.i18n.confirmDelete)) {
			event.preventDefault();
		}
	});

	$(document).on('submit', 'form[data-parag-mail-inspector-confirm]', function (event) {
		if (!window.confirm($(this).data('parag-mail-inspector-confirm'))) {
			event.preventDefault();
		}
	});

	$(document).on('click', '[data-parag-mail-inspector-tab]', function () {
		var tab = $(this).data('parag-mail-inspector-tab');

		$('[data-parag-mail-inspector-tab]').removeClass('is-active');
		$('[data-parag-mail-inspector-panel]').removeClass('is-active');
		$(this).addClass('is-active');
		$('[data-parag-mail-inspector-panel="' + tab + '"]').addClass('is-active');
	});
})(jQuery);
