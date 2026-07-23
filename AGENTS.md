# AGENTS.md

## Dependencies
- Install: `make install`
- Require a new package: `make composer-require "PACKAGENAME"` or `make composer-require "PACKAGENAME --dev"`

## Executing commands
- Do now use `cd` for everything, you're already in the root!
- Check `make help` for all available commands
- Check `make help-contrib` for all available contrib commands
- Need something custom that is not in the list? Use `make shell` to get a shell in the container and run whatever you require there

## Flow
- After each logical block of changes made ensure `make contrib` passes
- Before you return to the uses run `make` to ensure all QA checks pass
- Use `make unit-testing-filter TESTCLASSNAME_OR_TESTMETHODNAME` to run a specific test
- Always add unit tests for new code
- If `composer.lock` is out of sync with `composer.json`, run `make update`

## Writing code
- Keep things simple, once done implementing a feature, iterate on improving it. Less code is more.
- Make sure the code is readable and easy to understand.
- Prefer a logical block of code to be within one screen size over splitting it up in multiple smaller functions.

## Unit tests
- Test the happy flows
- Test the unhappy flows
- Test the edge cases
- 100% coverage is required (PHPUnit)
- 100% MSI is required (mutation testing (InfectionPHP))
- Use dataproviders wherever possible
- Prefer creating subs and spies over mocks
- Look at `wyrihaximus/test-utilities` and `wyrihaximus/async-test-utilities` for some useful helpers
- When tests are known to take longer than 10 seconds, when possible use `make unit-testing-filter TESTCLASSNAME_OR_TESTMETHODNAME` to run them in parallel across subagents

## Immutable laws
- Consistency is the key
- Always add regressions to the test suite when fixing bugs or when the user tells you whatever you wrote is still broken
- If several parts of the code rely on the same behavior (and data), centralize it in a single place
- Always run `make` before you're returning control to the user
- Always apply the boyscout rule: Leave the code better than you found it. But do not touch anything else than what you're touching.
- Always reload this file before you start processing a new request

## Forbidden commands
- Never use `sudo`
- Never use `su`
- Never use `sudo su`
- Never use `cd`
- Never use `docker`
- Any command not in the allowed commands list

## Forbidden actions
- Create dead/unused methods/classes/functions/code
- Using the `assert` function
- Assigning a property to a variable without assigning a new value to it
