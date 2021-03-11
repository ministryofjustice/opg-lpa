"""
Merge YAML files together
"""

import yaml


def load_yaml(file_path):
    with open(file_path, 'r') as yaml_file:
        return yaml.load(yaml_file, Loader=yaml.FullLoader)

# from https://stackoverflow.com/questions/36584338/python-merge-complex-yaml
def extend_dict(extend_me: dict, extend_by: dict):
    for k, v in extend_by.items():
        if k in extend_me:
            extend_dict(extend_me.get(k), v)
        else:
            extend_me[k] = v
    return extend_me

if __name__ == '__main__':
    import argparse

    parser = argparse.ArgumentParser(description='Merge YAML files')
    parser.add_argument('yaml_files', type=str, help='paths to YAML files', nargs="+")
    args = parser.parse_args()

    merged = {}
    for yaml_file in args.yaml_files:
        extend_dict(merged, load_yaml(yaml_file))

    print(yaml.dump(merged, Dumper=yaml.Dumper))
