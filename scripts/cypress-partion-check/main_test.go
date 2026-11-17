package main

import (
	"slices"
	"testing"

	"github.com/google/go-cmp/cmp/cmpopts"
	"gotest.tools/v3/assert"
)

func TestParseYAMLExtractsTagsAndFeatureFileNames(t *testing.T) {
	testYaml := `
jobs:
  cypress_tests_1:
    name: Cypress tests with specs
    with:
      cypress_specs: |
        Test1.feature
        Test2.feature
  cypress_tests_2:
    name: Cypress tests with tags
    with:
      cypress_tags: "@tag1"
  cypress_tests_3:
    name: Another cypress tests with tags
    with:
      cypress_tags: "@tag2 @tag3"
`
	ciTags, fileNames, err := parseYAML([]byte(testYaml))
	if err != nil {
		t.Fatalf("error parsing YAML: %v", err)
	}

	slices.Sort(ciTags)
	slices.Sort(fileNames)

	assert.DeepEqual(t, ciTags, []string{"@PartOfStitchedRun", "@Signup", "@tag1", "@tag2", "@tag3"})
	assert.DeepEqual(t, fileNames, []string{"Test1.feature", "Test2.feature"})
}

func TestExtractFeatureFileTags(t *testing.T) {
	tags, err := extractFeatureFileTags("./Test1.feature")
	if err != nil {
		t.Fatalf("error parsing Test1.feature: %v", err)
	}

	moreTags, err := extractFeatureFileTags("./Test2.feature")

	if err != nil {
		t.Fatalf("error parsing Test2.feature: %v", err)
	}

	slices.Sort(tags)
	slices.Sort(moreTags)

	assert.DeepEqual(t, tags, []string{"@Tag1", "@Tag2", "@Tag3"})
	assert.DeepEqual(t, moreTags, []string{"@Tag4", "@Tag5", "@Tag6"})
}

func TestFindUncoveredAndMissingFeatureFiles(t *testing.T) {
	tests := []struct {
		name                 string
		featureFilePaths     []string
		yamlFeatureFileNames []string
		ciTags               []string
		wantUncovered        []string
		wantMissing          []string
	}{
		{
			name:                 "nil when files have tags and filenames are listed in YAML",
			featureFilePaths:     []string{"Test1.feature", "Test2.feature"},
			yamlFeatureFileNames: []string{"Test1.feature"},
			ciTags:               []string{"@Tag5"},
			wantUncovered:        []string{},
			wantMissing:          []string{},
		},
		{
			name:                 "nil when files are covered by tags only",
			featureFilePaths:     []string{"Test1.feature", "Test2.feature"},
			yamlFeatureFileNames: []string{},
			ciTags:               []string{"@Tag1", "@Tag6"},
			wantUncovered:        []string{},
			wantMissing:          []string{},
		},
		{
			name:                 "nil when filenames are listed in YAML only",
			featureFilePaths:     []string{"Test1.feature", "Test2.feature"},
			yamlFeatureFileNames: []string{"Test1.feature", "Test2.feature"},
			ciTags:               []string{},
			wantUncovered:        []string{},
			wantMissing:          []string{},
		},
		{
			name:                 "reports uncovered when tags do not appear in feature files",
			featureFilePaths:     []string{"Test1.feature", "Test2.feature"},
			yamlFeatureFileNames: []string{},
			ciTags:               []string{"@Tag99"},
			wantUncovered:        []string{"Test1.feature", "Test2.feature"},
			wantMissing:          []string{},
		},
		{
			name:                 "reports uncovered feature file has no tags",
			featureFilePaths:     []string{"Test1.feature", "Test2.feature", "TaglessTest.feature"},
			yamlFeatureFileNames: []string{"Test1.feature", "Test2.feature"},
			ciTags:               []string{},
			wantUncovered:        []string{"TaglessTest.feature"},
			wantMissing:          []string{},
		},
		{
			name:                 "reports uncovered and missing when YAML lists a different file",
			featureFilePaths:     []string{"Test1.feature", "Test2.feature"},
			yamlFeatureFileNames: []string{"Test3.feature"},
			ciTags:               []string{},
			wantUncovered:        []string{"Test1.feature", "Test2.feature"},
			wantMissing:          []string{"Test3.feature"},
		},
		{
			name:                 "reports uncovered when feature file has no tags",
			featureFilePaths:     []string{"TaglessTest.feature"},
			yamlFeatureFileNames: []string{},
			ciTags:               []string{"@Tag1"},
			wantUncovered:        []string{"TaglessTest.feature"},
			wantMissing:          []string{},
		},
		{
			name:                 "reports only uncovered files when some are covered by tags",
			featureFilePaths:     []string{"Test1.feature", "Test2.feature", "TaglessTest.feature"},
			yamlFeatureFileNames: []string{},
			ciTags:               []string{"@Tag1"},
			wantUncovered:        []string{"TaglessTest.feature", "Test2.feature"},
			wantMissing:          []string{},
		},
		{
			name:                 "nil when ignored spec is untagged and not listed in YAML",
			featureFilePaths:     []string{"Ping.feature"},
			yamlFeatureFileNames: []string{},
			ciTags:               []string{},
			wantUncovered:        []string{},
			wantMissing:          []string{},
		},
		{
			name:                 "reports mixed when covered by tag, listed in YAML, ignored and uncovered",
			featureFilePaths:     []string{"Test1.feature", "Test2.feature", "TaglessTest.feature", "Ping.feature"},
			yamlFeatureFileNames: []string{"TaglessTest.feature"},
			ciTags:               []string{"@Tag1"},
			wantUncovered:        []string{"Test2.feature"},
			wantMissing:          []string{},
		},
		{
			name:                 "reports missing once when the same filename is listed twice in YAML",
			featureFilePaths:     []string{"Test1.feature"},
			yamlFeatureFileNames: []string{"Test1.feature", "Test3.feature", "Test3.feature"},
			ciTags:               []string{},
			wantUncovered:        []string{},
			wantMissing:          []string{"Test3.feature"},
		},
		{
			name:                 "reports all YAML filenames as missing when there are no feature files",
			featureFilePaths:     []string{},
			yamlFeatureFileNames: []string{"Test1.feature", "Test2.feature"},
			ciTags:               []string{},
			wantUncovered:        []string{},
			wantMissing:          []string{"Test1.feature", "Test2.feature"},
		},
		{
			name:                 "matches YAML filenames against the base name of feature file paths",
			featureFilePaths:     []string{"./Test1.feature"},
			yamlFeatureFileNames: []string{"Test1.feature"},
			ciTags:               []string{},
			wantUncovered:        []string{},
			wantMissing:          []string{},
		},
		{
			name:                 "nil when all inputs are empty",
			featureFilePaths:     []string{},
			yamlFeatureFileNames: []string{},
			ciTags:               []string{},
			wantUncovered:        []string{},
			wantMissing:          []string{},
		},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			uncovered, missing, err := findUncoveredAndMissingFeatureFiles(tc.featureFilePaths, tc.yamlFeatureFileNames, tc.ciTags)
			if err != nil {
				t.Fatalf("error finding uncovered and missing feature files: %v", err)
			}

			slices.Sort(uncovered)
			slices.Sort(missing)

			assert.DeepEqual(t, uncovered, tc.wantUncovered, cmpopts.EquateEmpty())
			assert.DeepEqual(t, missing, tc.wantMissing, cmpopts.EquateEmpty())
		})
	}
}

func TestFindUncoveredAndMissingFeatureFilesErrors(t *testing.T) {
	tests := []struct {
		name                 string
		featureFilePaths     []string
		yamlFeatureFileNames []string
		ciTags               []string
	}{
		{
			name:                 "returns error when feature file cannot be read",
			featureFilePaths:     []string{"DoesNotExist.feature"},
			yamlFeatureFileNames: []string{},
			ciTags:               []string{},
		},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			uncovered, missing, err := findUncoveredAndMissingFeatureFiles(tc.featureFilePaths, tc.yamlFeatureFileNames, tc.ciTags)

			assert.Assert(t, err != nil)
			assert.Assert(t, uncovered == nil)
			assert.Assert(t, missing == nil)
		})
	}
}

func TestSpecsForSuite(t *testing.T) {
	testYaml := `
jobs:
  cypress_tests_1:
    with:
      suite_name: "Remaining 1"
      cypress_specs: |
        Test1.feature
        Test2.feature
  cypress_tests_2:
    with:
      suite_name: "Remaining 2"
      cypress_specs: |
        Test3.feature
`
	specs, err := specsForSuite([]byte(testYaml), "Remaining 1")
	assert.NilError(t, err)
	assert.DeepEqual(t, specs, []string{"Test1.feature", "Test2.feature"})

	_, err = specsForSuite([]byte(testYaml), "Missing")
	assert.ErrorContains(t, err, "Missing")
}
