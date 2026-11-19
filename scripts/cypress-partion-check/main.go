package main

import (
	"bufio"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"slices"
	"strings"

	"gopkg.in/yaml.v3"
)

type ActionYAML struct {
	Jobs map[string]Job `yaml:"jobs"`
}

type Job struct {
	With With `yaml:"with"`
}

type With struct {
	SuiteName    string `yaml:"suite_name"`
	CypressTags  string `yaml:"cypress_tags"`
	CypressSpecs string `yaml:"cypress_specs"`
}

const (
	featureFilesPath = "../../cypress/e2e"
	// Run by cypress_tests.yml before every suite.
	alwaysRunTag = "@Signup"
	// Combined into the stitched feature files by cypress/stitch.py.
	stitchedPartTag = "@PartOfStitchedRun"
)

// Workflow that splits the remaining feature files into named suites; read by the "specs" subcommand.
const remainingWorkflowPath = "../../.github/workflows/cypress_remaining_tests.yml"

// Workflow files that define which feature files and tags run in CI.
var workflowPaths = []string{
	"../../.github/workflows/workflow_pr.yml",
	"../../.github/workflows/cypress_remaining_tests.yml",
}

// Specs we don't run in CI (excluded via tags: @Ping, @CorrespondentReuse,
// @AdminSystemMessage, @CheckoutPaymentGateway).
var ignoredSpecs = []string{
	"Ping.feature",
	"CorrespondentReuse.feature",
	"AdminSystemMessage.feature",
}

// Scans feature files line by line and collects all tags found
func extractFeatureFileTags(path string) ([]string, error) {
	f, err := os.Open(path)
	if err != nil {
		return nil, err
	}
	defer f.Close()

	var tags []string
	scanner := bufio.NewScanner(f)
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		if strings.HasPrefix(line, "@") {
			tags = append(tags, strings.Fields(line)...)
		}
	}

	slices.Sort(tags)
	tags = slices.Compact(tags)
	return tags, scanner.Err()
}

func parseYAML(yamlData []byte) ([]string, []string, error) {
	actionYaml := &ActionYAML{}
	if err := yaml.Unmarshal(yamlData, actionYaml); err != nil {
		return nil, nil, err
	}

	var yamlFeatureFileNames []string
	ciTags := []string{alwaysRunTag, stitchedPartTag}

	for _, job := range actionYaml.Jobs {
		for spec := range strings.SplitSeq(job.With.CypressSpecs, "\n") {
			if spec = strings.TrimSpace(spec); spec != "" {
				yamlFeatureFileNames = append(yamlFeatureFileNames, spec)
			}
		}
		ciTags = append(ciTags, strings.Fields(job.With.CypressTags)...)
	}

	return ciTags, yamlFeatureFileNames, nil
}

// Returns the feature files listed for the job whose suite_name matches, in workflow order.
func specsForSuite(yamlData []byte, suiteName string) ([]string, error) {
	actionYaml := &ActionYAML{}
	if err := yaml.Unmarshal(yamlData, actionYaml); err != nil {
		return nil, err
	}

	for _, job := range actionYaml.Jobs {
		if job.With.SuiteName == suiteName {
			return strings.Fields(job.With.CypressSpecs), nil
		}
	}

	return nil, fmt.Errorf("no job with suite_name %v found", suiteName)
}

func findUncoveredAndMissingFeatureFiles(featureFilePaths, yamlFeatureFileNames, ciTags []string) ([]string, []string, error) {
	var featureFileInRepo, uncovered, missingFromRepo []string

	for _, file := range featureFilePaths {
		name := filepath.Base(file)
		featureFileInRepo = append(featureFileInRepo, name)
		if slices.Contains(ignoredSpecs, name) || slices.Contains(yamlFeatureFileNames, name) {
			continue
		}
		tags, err := extractFeatureFileTags(file)
		if err != nil {
			return nil, nil, err
		}
		if !slices.ContainsFunc(tags, func(t string) bool { return slices.Contains(ciTags, t) }) {
			uncovered = append(uncovered, name)
		}
	}

	for _, name := range yamlFeatureFileNames {
		if !slices.Contains(featureFileInRepo, name) {
			missingFromRepo = append(missingFromRepo, name)
		}
	}

	slices.Sort(uncovered)
	uncovered = slices.Compact(uncovered)

	slices.Sort(missingFromRepo)
	missingFromRepo = slices.Compact(missingFromRepo)

	return uncovered, missingFromRepo, nil
}

// Prints the feature files for a suite on one line, for use from the Makefile:
// go run . specs "Remaining 1"
func printSuiteSpecs(suiteName string) {
	data, err := os.ReadFile(remainingWorkflowPath)
	if err != nil {
		log.Fatalf("error: %v", err)
	}

	specs, err := specsForSuite(data, suiteName)
	if err != nil {
		log.Fatalf("error: %v", err)
	}

	fmt.Println(strings.Join(specs, " "))
}

func main() {
	if len(os.Args) == 3 && os.Args[1] == "specs" {
		printSuiteSpecs(os.Args[2])
		return
	}

	var ciTags, yamlFeatureFileNames []string
	for _, path := range workflowPaths {
		data, err := os.ReadFile(path)
		if err != nil {
			log.Fatalf("error: %v", err)
		}

		tags, names, err := parseYAML(data)
		if err != nil {
			log.Fatalf("error parsing %s: %v", path, err)
		}

		ciTags = append(ciTags, tags...)
		yamlFeatureFileNames = append(yamlFeatureFileNames, names...)
	}

	featureFilePaths, err := filepath.Glob(featureFilesPath + "/*.feature")
	if err != nil {
		log.Fatalf("error: %v", err)
	}

	uncovered, missingFromRepo, err := findUncoveredAndMissingFeatureFiles(featureFilePaths, yamlFeatureFileNames, ciTags)
	if err != nil {
		log.Fatalf("error: %v", err)
	}

	fmt.Println("Spec files not run by any tag or explicit spec list:")
	fmt.Println(uncovered)
	fmt.Println("\nSpecs listed in workflow but not found in repo:")
	fmt.Println(missingFromRepo)

	if len(uncovered) > 0 || len(missingFromRepo) > 0 {
		os.Exit(1)
	}
	fmt.Println("\nAll spec files are accounted for")
}
