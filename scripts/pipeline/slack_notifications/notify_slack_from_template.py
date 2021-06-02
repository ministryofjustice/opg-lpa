
import os
import argparse
import json
import re
import jinja2
from jinja2.loaders import FileSystemLoader
from slack_sdk import WebClient


class TemplateRenderer:

    def __init__(self, template_name, template_folder, replacement_vars):
        self.template_name = template_name
        print(replacement_vars)
        self.template_environment = jinja2.Environment(loader=FileSystemLoader(template_folder))
        self.template_vars = { **dict([(k,self.sanitize(v)) for k, v in os.environ.items()]),
                                **{k : self.sanitize(v) for k,v in replacement_vars.items()}}
        print(self.template_vars)

    def sanitize(key,value):
        return json.dumps(value)[1:-1]

    def GetTemplate(self):
        tmpl = self.template_environment.get_template(self.template_name)
        output = tmpl.render(**self.template_vars)
        return output


class SlackNotifier:
    def __init__(self, slack_token, slack_channel):
        self.slack_channel = slack_channel
        self.slack_token = slack_token

    def SendNotification(self, output):
        client = WebClient(token=self.slack_token)
        message = json.loads(output)
        client.chat_postMessage(channel=self.slack_channel,
                                attachments=message['attachments'],
                                blocks=message['blocks'])


#https://www.geeksforgeeks.org/python-key-value-pair-using-argparse/

# create a keyvalue class
class keyvalue(argparse.Action):
	# Constructor calling
	def __call__( self , parser, namespace,
				values, option_string = None):
		setattr(namespace, self.dest, dict())

		for value in values:
			# split it into key and value
			key, value = value.split('=')
			# assign into dictionary
			getattr(namespace, self.dest)[key] = value


def main():
    parser = argparse.ArgumentParser(description="runs a slack notification")
    parser.add_argument("--template",help="defines a template name to look up")
    parser.add_argument("--template_folder",nargs="?", default="templates",help="folder for the templates")
    parser.add_argument("--slack_token", help="slack api token to use")
    parser.add_argument("--slack_channel", help="slack channel to post to")
    parser.add_argument("--replacement_vars",help="additional replacement variables to pass in", nargs="*", action=keyvalue, default=dict())
    args  = parser.parse_args()

    renderer = TemplateRenderer(args.template, args.template_folder, args.replacement_vars)
    notifier = SlackNotifier(args.slack_token, args.slack_channel)
    notifier.SendNotification(renderer.GetTemplate())


if __name__ == "__main__":
    main()
