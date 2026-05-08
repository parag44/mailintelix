(function ($) {
	'use strict';

	function openModal(content) {
		$('#simple-mail-logger-modal-content').html(content);
		$('#simple-mail-logger-preview-modal').addClass('is-open').attr('aria-hidden', 'false');
	}

	function closeModal() {
		$('#simple-mail-logger-preview-modal').removeClass('is-open').attr('aria-hidden', 'true');
		$('#simple-mail-logger-modal-content').empty();
	}

	$(document).on('click', '.simple-mail-logger-view-log', function () {
		var logId = $(this).data('log-id');

		openModal('<p>' + SimpleMailLoggerAdmin.i18n.loading + '</p>');

		$.post(SimpleMailLoggerAdmin.ajaxUrl, {
			action: 'simple_mail_logger_get_log',
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

	$(document).on('click', '[data-simple-mail-logger-close]', closeModal);

	$(document).on('click', '#simple-mail-logger-preview-modal', function (event) {
		if (event.target === this) {
			closeModal();
		}
	});

	$(document).on('keydown', function (event) {
		if (event.key === 'Escape') {
			closeModal();
		}
	});

	$(document).on('click', '.simple-mail-logger-delete-link', function (event) {
		if (!window.confirm(SimpleMailLoggerAdmin.i18n.confirmDelete)) {
			event.preventDefault();
		}
	});

	$(document).on('submit', 'form[data-simple-mail-logger-confirm]', function (event) {
		if (!window.confirm($(this).data('simple-mail-logger-confirm'))) {
			event.preventDefault();
		}
	});

	$(document).on('click', '[data-simple-mail-logger-tab]', function () {
		var tab = $(this).data('simple-mail-logger-tab');

		$('[data-simple-mail-logger-tab]').removeClass('is-active');
		$('[data-simple-mail-logger-panel]').removeClass('is-active');
		$(this).addClass('is-active');
		$('[data-simple-mail-logger-panel="' + tab + '"]').addClass('is-active');
	});

	$(document).on('change', '#simple-mail-logger-smtp-enabled', function () {
		$('.simple-mail-logger-settings-form').toggleClass('is-smtp-enabled', $(this).is(':checked'));
	});
})(jQuery);
