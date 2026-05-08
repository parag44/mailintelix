(function ($) {
	'use strict';

	function openModal(content) {
		$('#mailtally-modal-content').html(content);
		$('#mailtally-preview-modal').addClass('is-open').attr('aria-hidden', 'false');
	}

	function closeModal() {
		$('#mailtally-preview-modal').removeClass('is-open').attr('aria-hidden', 'true');
		$('#mailtally-modal-content').empty();
	}

	$(document).on('click', '.mailtally-view-log', function () {
		var logId = $(this).data('log-id');

		openModal('<p>' + SimpleMailLoggerAdmin.i18n.loading + '</p>');

		$.post(SimpleMailLoggerAdmin.ajaxUrl, {
			action: 'mailtally_get_log',
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

	$(document).on('click', '[data-mailtally-close]', closeModal);

	$(document).on('click', '#mailtally-preview-modal', function (event) {
		if (event.target === this) {
			closeModal();
		}
	});

	$(document).on('keydown', function (event) {
		if (event.key === 'Escape') {
			closeModal();
		}
	});

	$(document).on('click', '.mailtally-delete-link', function (event) {
		if (!window.confirm(SimpleMailLoggerAdmin.i18n.confirmDelete)) {
			event.preventDefault();
		}
	});

	$(document).on('submit', 'form[data-mailtally-confirm]', function (event) {
		if (!window.confirm($(this).data('mailtally-confirm'))) {
			event.preventDefault();
		}
	});

	$(document).on('click', '[data-mailtally-tab]', function () {
		var tab = $(this).data('mailtally-tab');

		$('[data-mailtally-tab]').removeClass('is-active');
		$('[data-mailtally-panel]').removeClass('is-active');
		$(this).addClass('is-active');
		$('[data-mailtally-panel="' + tab + '"]').addClass('is-active');
	});
})(jQuery);
