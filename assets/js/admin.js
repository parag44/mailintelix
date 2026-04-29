(function ($) {
	'use strict';

	function openModal(content) {
		$('#mailintelix-modal-content').html(content);
		$('#mailintelix-preview-modal').addClass('is-open').attr('aria-hidden', 'false');
	}

	function closeModal() {
		$('#mailintelix-preview-modal').removeClass('is-open').attr('aria-hidden', 'true');
		$('#mailintelix-modal-content').empty();
	}

	$(document).on('click', '.mailintelix-view-log', function () {
		var logId = $(this).data('log-id');

		openModal('<p>' + MailIntelixAdmin.i18n.loading + '</p>');

		$.post(MailIntelixAdmin.ajaxUrl, {
			action: 'mailintelix_get_log',
			nonce: MailIntelixAdmin.nonce,
			log_id: logId
		}).done(function (response) {
			if (response && response.success && response.data.html) {
				openModal(response.data.html);
			} else {
				openModal('<p>' + MailIntelixAdmin.i18n.error + '</p>');
			}
		}).fail(function () {
			openModal('<p>' + MailIntelixAdmin.i18n.error + '</p>');
		});
	});

	$(document).on('click', '[data-mailintelix-close]', closeModal);

	$(document).on('click', '#mailintelix-preview-modal', function (event) {
		if (event.target === this) {
			closeModal();
		}
	});

	$(document).on('keydown', function (event) {
		if (event.key === 'Escape') {
			closeModal();
		}
	});

	$(document).on('click', '.mailintelix-delete-link', function (event) {
		if (!window.confirm(MailIntelixAdmin.i18n.confirmDelete)) {
			event.preventDefault();
		}
	});

	$(document).on('submit', 'form[data-mailintelix-confirm]', function (event) {
		if (!window.confirm($(this).data('mailintelix-confirm'))) {
			event.preventDefault();
		}
	});

	$(document).on('click', '[data-mailintelix-tab]', function () {
		var tab = $(this).data('mailintelix-tab');

		$('[data-mailintelix-tab]').removeClass('is-active');
		$('[data-mailintelix-panel]').removeClass('is-active');
		$(this).addClass('is-active');
		$('[data-mailintelix-panel="' + tab + '"]').addClass('is-active');
	});
})(jQuery);
