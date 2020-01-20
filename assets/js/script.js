document.addEventListener('DOMContentLoaded', function(event) {

    var licenseTable = {
        btnHideLicense: document.querySelectorAll('.lmfwc-license-key-hide'),
        btnShowLicense: document.querySelectorAll('.lmfwc-license-key-show'),
        btnCopyToClipboard: document.querySelectorAll('code.lmfwc-placeholder'),
        txtCopiedToClipboard: document.querySelector('.lmfwc-txt-copied-to-clipboard'),
        bindEventListeners: function() {
            var that = this;

            if (this.btnHideLicense) {
                for (var i = 0; i < this.btnHideLicense.length; i++) {
                    this.btnHideLicense[i].addEventListener('click', function() {
                        that.hideLicenseKey(this);
                    });
                }
            }

            if (this.btnShowLicense) {
                for (var i = 0; i < this.btnShowLicense.length; i++) {
                    this.btnShowLicense[i].addEventListener('click', function() {
                        that.showLicenseKey(this);
                    });
                }
            }

            if (this.btnCopyToClipboard) {
                for (var i = 0; i < this.btnCopyToClipboard.length; i++) {
                    this.btnCopyToClipboard[i].addEventListener('click', function(e) {
                        that.copyToClipboard(this, e);
                    });
                }
            }
        },
        hideLicenseKey: function(el) {
            var code = el.parentNode.parentNode.previousSibling.previousSibling;

            code.innerText = '';
            code.classList.add('empty');
        },
        showLicenseKey: function(el) {
            var licenseKeyId = parseInt(el.dataset.id);
            var spinner      = el.parentNode.parentNode.previousSibling;
            var code         = spinner.previousSibling;

            spinner.style.opacity = 1;

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'lmfwc_show_license_key',
                    show: license.show,
                    id: licenseKeyId
                },
                success: function(response) {
                    code.classList.remove('empty');
                    code.innerText = response;
                },
                error: function(response) {
                    console.log(response);
                },
                complete: function() {
                    spinner.style.opacity = 0;
                }
            });
        },
        copyToClipboard: function(el, e) {
            // Copy to clipboard
            var str = el.innerText.toString();

            if (!str) return;

            const textArea = document.createElement('textarea');
            textArea.value = str;
            textArea.setAttribute('readonly', '');
            textArea.style.position = 'absolute';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            const selected =
                document.getSelection().rangeCount > 0
                    ? document.getSelection().getRangeAt(0)
                    : false;
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            if (selected) {
                document.getSelection().removeAllRanges();
                document.getSelection().addRange(selected);
            }

            // Display info
            var copied = document.createElement('div');
            copied.classList.add('lmfwc-clipboard');
            copied.style.position = 'absolute';
            copied.style.left = e.clientX.toString() + 'px';
            copied.style.top = (window.pageYOffset + e.clientY).toString() + 'px';
            copied.innerText = document.querySelector('.lmfwc-txt-copied-to-clipboard').innerText.toString();
            document.body.appendChild(copied);

            setTimeout(function() {
                copied.style.opacity = '0';
            }, 700);
            setTimeout(function() {
                document.body.removeChild(copied);
            }, 1500);
        },
        init: function() {
            this.bindEventListeners();
        }
    };

    var licenseInstanceTable = {
        btnHideLicenseInstance: document.querySelectorAll('.lmfwc-license-instance-key-hide'),
        btnShowLicenseInstance: document.querySelectorAll('.lmfwc-license-instance-key-show'),
        btnCopyToClipboard: document.querySelectorAll('code.lmfwc-placeholder'),
        txtCopiedToClipboard: document.querySelector('.lmfwc-txt-copied-to-clipboard'),
        bindEventListeners: function() {
            var that = this;

            if (this.btnHideLicenseInstance) {
                for (var i = 0; i < this.btnHideLicenseInstance.length; i++) {
                    this.btnHideLicenseInstance[i].addEventListener('click', function() {
                        that.hideLicenseInstanceKey(this);
                    });
                }
            }

            if (this.btnShowLicenseInstance) {
                for (var i = 0; i < this.btnShowLicenseInstance.length; i++) {
                    this.btnShowLicenseInstance[i].addEventListener('click', function() {
                        that.showLicenseInstanceKey(this);
                    });
                }
            }

            if (this.btnCopyToClipboard) {
                for (var i = 0; i < this.btnCopyToClipboard.length; i++) {
                    this.btnCopyToClipboard[i].addEventListener('click', function(e) {
                        that.copyToClipboard(this, e);
                    });
                }
            }
        },
        hideLicenseInstanceKey: function(el) {
            var code = el.parentNode.parentNode.previousSibling.previousSibling;

            code.innerText = '';
            code.classList.add('empty');
        },
        showLicenseInstanceKey: function(el) {
            var licenseInstanceKeyId = parseInt(el.dataset.id);
            var spinner              = el.parentNode.parentNode.previousSibling;
            var code                 = spinner.previousSibling;

            spinner.style.opacity = 1;

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'lmfwc_show_license_instance_key',
                    show: licenseInstance.show,
                    id: licenseInstanceKeyId
                },
                success: function(response) {
                    code.classList.remove('empty');
                    code.innerText = response;
                },
                error: function(response) {
                    console.log(response);
                },
                complete: function() {
                    spinner.style.opacity = 0;
                }
            });
        },
        copyToClipboard: function(el, e) {
            // Copy to clipboard
            var str = el.innerText.toString();

            if (!str) return;

            const textArea = document.createElement('textarea');
            textArea.value = str;
            textArea.setAttribute('readonly', '');
            textArea.style.position = 'absolute';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            const selected =
                document.getSelection().rangeCount > 0
                    ? document.getSelection().getRangeAt(0)
                    : false;
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            if (selected) {
                document.getSelection().removeAllRanges();
                document.getSelection().addRange(selected);
            }

            // Display info
            var copied = document.createElement('div');
            copied.classList.add('lmfwc-clipboard');
            copied.style.position = 'absolute';
            copied.style.left = e.clientX.toString() + 'px';
            copied.style.top = (window.pageYOffset + e.clientY).toString() + 'px';
            copied.innerText = document.querySelector('.lmfwc-txt-copied-to-clipboard').innerText.toString();
            document.body.appendChild(copied);

            setTimeout(function() {
                copied.style.opacity = '0';
            }, 700);
            setTimeout(function() {
                document.body.removeChild(copied);
            }, 1500);
        },
        init: function() {
            this.bindEventListeners();
        }
    };

    var orderLicenses = {
        btnShow: document.querySelector('.lmfwc-license-keys-show-all'),
        btnHide: document.querySelector('.lmfwc-license-keys-hide-all'),
        getLicenseKeyIds: function() {
            var licenseKeyIds = [];
            var codeList      = this.btnShow.parentNode.previousSibling.children;

            for(var i = 0, length = codeList.length; i < length; i++){
                licenseKeyIds.push(parseInt(codeList[i].children[0].dataset.id));
            }

            return licenseKeyIds;
        },
        bindShow: function() {
            if (!this.btnShow) return;

            var spinner = this.btnShow.nextSibling.nextSibling;
            var licenseKeyIds = this.getLicenseKeyIds();

            this.btnShow.addEventListener('click', function() {
                spinner.style.opacity = 1;

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lmfwc_show_all_license_keys',
                        show_all: license.show_all,
                        ids: JSON.stringify(licenseKeyIds)
                    },
                    success: function(response) {
                        var licenseKeys = response;

                        for (var id in licenseKeys) {
                            var licenseKey = document.querySelector('.lmfwc-placeholder[data-id="' + id +'"]');
                            licenseKey.classList.remove('empty');
                            licenseKey.innerText = licenseKeys[id];
                        }
                    },
                    error: function(response) {
                        console.log(response);
                    },
                    complete: function() {
                        spinner.style.opacity = 0;
                    }
                });
            });
        },
        bindHide: function() {
            if (!this.btnHide) return;

            var licenseKeyIds = this.getLicenseKeyIds();

            this.btnHide.addEventListener('click', function() {
                for (var id in licenseKeyIds) {
                    var licenseKey = document.querySelector('.lmfwc-placeholder[data-id="' + licenseKeyIds[id] +'"]');
                    licenseKey.classList.add('empty');
                    licenseKey.innerText = '';
                }
            });
        },
        init: function() {
            this.bindShow();
            this.bindHide();
        }
    };

    licenseTable.init();
    licenseInstanceTable.init();
    orderLicenses.init();
});