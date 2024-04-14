VERSION ?= false

# Publish the composer package via git tag
publish:
	@if [ "$(VERSION)" = "false" ]; then \
		echo "Please provide a version number"; \
		exit 1; \
	fi
	git tag -a $(VERSION) -m "Release $(VERSION)"
	git push origin $(VERSION)
