# -*- coding: iso-8859-1 -*-
"""
    MoinMoin - recaptcha support

    Based heavily on the textcha support in textcha.py

    @copyright: 2011 by Steve McIntyre
    @license: GNU GPL, see COPYING for details.
"""

from MoinMoin import log
from recaptcha.client import captcha
import sys

logging = log.getLogger(__name__)

from MoinMoin import wikiutil

class ReCaptcha(object):
    """ Recaptcha support """

    def __init__(self, request):
        """ Initialize the Recaptcha setup.

            @param request: the request object
        """
        self.request = request
        self.user_info = request.user.valid and request.user.name or request.remote_addr
        cfg = request.cfg

        try:
            if cfg.recaptcha_public_key:
                self.public_key = cfg.recaptcha_public_key
            if cfg.recaptcha_private_key:
                self.private_key = cfg.recaptcha_private_key
        except:
            self.public_key = None
            self.private_key = None

    def is_enabled(self):
        """ check if we're configured, i.e. we have a key
        """
        if (self.public_key and self.private_key):
            return True
        return False

    def check_answer_from_form(self, form=None):
        if self.is_enabled():
            if form is None:
                form = self.request.form
            challenge = form.get('recaptcha_challenge_field')
            response = form.get('recaptcha_response_field')
            captcha_result = captcha.submit(challenge, response, self.private_key, self.request.remote_addr)
            if captcha_result.is_valid:
                logging.info(u"ReCaptcha: OK.")
                return True
            else:
                logging.info(u"ReCaptcha: failed, error code %s." % captcha_result.error_code)
                return False
        else:
            return True

    def render(self, form=None):
        """ Checks if ReCaptchas are enabled and returns HTML for one,
            or an empty string if they are not enabled.

            @return: unicode result html
        """
        if self.is_enabled():
            result = captcha.displayhtml(self.public_key, use_ssl = True)
        else:
            result = u''
        return result
