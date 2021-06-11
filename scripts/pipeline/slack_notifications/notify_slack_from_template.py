
import os
import argparse
import json
import jinja2
from jinja2.loaders import FileSystemLoader
from slack_sdk import WebClient


class TemplateRenderer:
    """Template renderer class
    """

    def __init__(self, template_file, template_folder, vars):
        """constructor

        Args:
            template_file (string): filename of the template to use
            template_folder (string):
            vars (dict(string, string)): a dictionary of replacement variables passed on the command line
        """
        self.template_file = template_file

        self.template_environment = jinja2.Environment(
            loader=FileSystemLoader(template_folder))

        # pull in  variables to use, and merge.
        self.template_vars = {**{k: self.sanitize(v) for k, v in vars.items()}}
        print(self.template_vars)

    def sanitize(key, value):
        """sanitize any strings passed from a dict to make it json compatible.

        Args:
            key (string): key of the item (unused, but needed for dicts)
            value (string): value of the item

        Returns:
            string: a sanitized string useful for json
        """
        return json.dumps(value)[1:-1]

    def render(self):
        """retrieves a template and renders based on the list of variables avaialble

        Returns:
            string : the rendered json output
        """
        tmpl = self.template_environment.get_template(self.template_file)
        return tmpl.render(**self.template_vars)


class SlackNotifier:
    """notifies slack based on the rendered output, slack token and channel
    """

    def __init__(self, slack_token, slack_channel):
        """constructor

        Args:
            slack_token (string): the slack app token to use
            slack_channel (string): the slack channel id to post to
        """
        self.slack_channel = slack_channel
        self.slack_token = slack_token

    def notify(self, message_json):
        """Send the notification based on the rendered output

        Args:
            message_json (string): the message in block / attachment format to
        """
        client = WebClient(token=self.slack_token)
        message = json.loads(message_json)
        client.chat_postMessage(channel=self.slack_channel,
                                attachments=message['attachments'],
                                blocks=message['blocks'])


# https://www.geeksforgeeks.org/python-key-value-pair-using-argparse/
class keyvalue(argparse.Action):
    # Constructor calling
    def __call__(self, parser, namespace,
                 values, option_string=None):
        setattr(namespace, self.dest, dict())

        for value in values:
            # split it into key and value
            key, val = value.split('=')
            # assign into dictionary
            getattr(namespace, self.dest)[key] = val


def main():
    parser = argparse.ArgumentParser(description="runs a slack notification")
    parser.add_argument(
        "--template_file", help="defines a template name to look up")
    parser.add_argument("--template_folder", nargs="?",
                        default="templates", help="folder for the templates")
    parser.add_argument("--slack_token", help="slack api token to use")
    parser.add_argument("--slack_channel", help="slack channel to post to")
    parser.add_argument("--vars", help="replacement variables to pass in",
                        nargs="*", action=keyvalue, default=dict())
    args = parser.parse_args()

    renderer = TemplateRenderer(
        args.template_file, args.template_folder, args.vars)
    notifier = SlackNotifier(args.slack_token, args.slack_channel)
    notifier.notify(renderer.render())


if __name__ == "__main__":
    main()
