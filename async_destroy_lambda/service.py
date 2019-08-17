import os
from subprocess import Popen, PIPE, CalledProcessError
import wget
from zipfile import ZipFile
import stat
from git import Repo


# Version of Terraform that we're using
TERRAFORM_VERSION = os.getenv('TERRAFORM_VERSION')

# Download URL for Terraform
TERRAFORM_DOWNLOAD_URL = 'https://releases.hashicorp.com/terraform/{0}/terraform_{0}_linux_amd64.zip'.format(
    TERRAFORM_VERSION)
# TERRAFORM_DOWNLOAD_URL = 'https://releases.hashicorp.com/terraform/{0}/terraform_{0}_darwin_amd64.zip'.format(
# TERRAFORM_VERSION)

# Paths where Terraform should be installed
TERRAFORM_DIR = os.path.join('/tmp', 'terraform_{}'.format(TERRAFORM_VERSION))
TERRAFORM_PATH = os.path.join(TERRAFORM_DIR, 'terraform')

# Git repository to work with, and where to clone it to
GIT_URL = os.getenv('GIT_URL')
REPO_DIR = os.getenv('REPO_DIR', "/tmp")

# Terraform config to work with
TF_CONFIG_FULL_PATH = os.path.join(REPO_DIR, os.getenv(
    'TF_CONFIG_PATH', 'terraform_environment'))

PROTECTED_WORKSPACES = ['default', 'preproduction', 'production']


def execute_terraform(args):
    """Terraform executor wrapper for subprocess that checks if a process runs correctly,
    and if not, returns error. Sets working directory to be
    the Terraform Config Path.
    """
    with Popen(args,
               stdout=PIPE,
               bufsize=1,
               universal_newlines=True,
               cwd=TF_CONFIG_FULL_PATH) as p:
        for line in p.stdout:
            print(line, end='')  # process line here

    if p.returncode != 0:
        raise CalledProcessError(p.returncode, p.args)


def install_terraform():
    """Install Terraform."""
    if os.path.exists(TERRAFORM_PATH):
        return
    else:
        print('downloading {} to /tmp/terraform.zip'.format(TERRAFORM_DOWNLOAD_URL))
        wget.download(TERRAFORM_DOWNLOAD_URL, '/tmp/terraform.zip')
        print(" ")

        with ZipFile('/tmp/terraform.zip', 'r') as zipObj:
            zipObj.extractall(TERRAFORM_DIR)
        st = os.stat(TERRAFORM_PATH)
        os.chmod(TERRAFORM_PATH, st.st_mode | stat.S_IEXEC)
        os.remove('/tmp/terraform.zip')
        print("download complete")


def check_terraform_version():
    print("check_terraform_version...")
    execute_terraform([TERRAFORM_PATH, '--version'])


def clone_repo():
    """Clone repository to get terraform config.
    :param GIT_URL: Repository with terraform configuration.
    :param REPO_DIR: Path to clone repository to.
    """
    print("Cloning repo {}...".format(GIT_URL))
    if (GIT_URL):
        if os.path.exists(TF_CONFIG_FULL_PATH):
            print("{} already exists, updating...".format(REPO_DIR))
            print("git pull master")
            # TODO: git pull here
        else:
            Repo.clone_from(GIT_URL, REPO_DIR)
            print("cloned {0} to {1}".format(GIT_URL, REPO_DIR))
    else:
        print("no repository passed")
        exit(1)


def terraform_init():
    """Initialise Terraform to configure remote state.
    """
    execute_terraform([TERRAFORM_PATH, 'init'])


def terraform_destroy(workspace):
    """Terraform Destroy, Destroys all resources in a terraform workspace.
    Also removes the workspace.
    :param workspace: Name of the S3 bucket where the plan is stored.
    """
    print("Destroying workspace {}...".format(workspace))
    if workspace == 'test_workspace':
        print("TEST MODE:")
        print("    terraform workspace select test_workspace")
        print("    terraform destroy test_workspace -lock-timeout=30s")
        print("    terraform workspace select default")
        print("    terraform workspace delete test_workspace")
        print("TEST MODE ENDS")
    elif workspace != 'test_workspace':
        execute_terraform([TERRAFORM_PATH, 'workspace', 'select', workspace])
        execute_terraform([TERRAFORM_PATH, 'destroy',
                           '--auto-approve', '-lock-timeout=30s'])
        execute_terraform([TERRAFORM_PATH, 'workspace', 'select', 'default'])
        execute_terraform([TERRAFORM_PATH, 'workspace', 'delete', workspace])


def handler(event, context):
    for record in event['Records']:
        workspace = record["body"]

        # exit early if workspace is protected
        if not workspace in PROTECTED_WORKSPACES:
            clone_repo()
            install_terraform()
            check_terraform_version()
            terraform_init()
            terraform_destroy(workspace)
        else:
            print("Workspace {} is protected. Terraform destroy steps skipped").format(
                str(workspace))
