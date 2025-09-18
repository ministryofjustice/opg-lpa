from pathlib import Path

_stitch_config = {
    "StitchedCreatePFLpa": {
        "tags": "@StitchedPF",
        "files": [
            "LpaTypePF",
            "DonorPF",
            "AttorneysPF",
            "ReusablePF",
            "ReplacementAttorneysPF",
            "CertProviderPF",
            "PeopleToNotifyPF",
            "InstructionsPreferencesPF",
            "SummaryPF",
            "ApplicantPF",
            "CorrespondentPF",
            "WhoAreYouPF",
            "RepeatApplicationPF",
            "FeeReductionPF",
            "CheckoutPF",
        ],
    },
    "StitchedCreateHWLpa": {
        "tags": "@StitchedHW",
        "files": [
            "LpaTypeHW",
            "DonorHW",
            "AttorneysHW",
            "ReusableHW",
            "ReplacementAttorneysHW",
            "CertProviderHW",
            "PeopleToNotifyHW",
            "InstructionsPreferencesHW",
            "SummaryHW",
            "ApplicantHW",
            "CorrespondentHW",
            "WhoAreYouHW",
            "RepeatApplicationHW",
            "FeeReductionHW",
            "CheckoutHW",
        ],
    },
    "StitchedClonePFLpa": {
        "tags": "@StitchedClone",
        "files": [
            "LpaTypePFClone",
            "DonorPF",
            "AttorneysPFClone",
            "ReplacementAttorneysPFClone",
            "CertProviderPF",
            "PeopleToNotifyPFClone",
            "InstructionsPreferencesPF",
            "SummaryPFClone",
            "ApplicantPFClone",
            "CorrespondentPFClone",
            "WhoAreYouPF",
            "RepeatApplicationHW",
            "FeeReductionPFClone",
            "CheckoutPFClone",
        ],
    },
}


def stitch_feature_files(feature_files_dir):
    """
    For each key in the _stitch_config, generate a stitched
    .feature file inside feature_files_dir, composed of the
    listed sub-files tacked together.

    If a file is being stitched, it MUST contain a line in the format

        # ** CUT Above Here **

    Any content above this line is removed before stitching.

    :param feature_files_dir: Path to the component files, and the
        output directory for the resulting stitched file
    """
    if not isinstance(feature_files_dir, Path):
        feature_files_dir = Path(feature_files_dir)

    for stitched_file_name, config in _stitch_config.items():
        output_file = Path(f"{feature_files_dir / stitched_file_name}.feature")
        print(f"Stitching file: {output_file}")

        with output_file.open("w") as out:
            out.write(config["tags"] + "\n")

            for component_file_name in config["files"]:
                full_path = Path(f"{feature_files_dir / component_file_name}.feature")
                with full_path.open() as f:
                    content = f.read()

                    # Cut out the lines above "# ** CUT Above Here **" (if present)
                    cut_comment_position = content.find("# ** CUT Above Here **")
                    if cut_comment_position > -1:
                        content = content[cut_comment_position:]

                    # append to the stitch file
                    out.write(content + "\n")

if __name__ == "__main__":
    stitch_feature_files("e2e")
