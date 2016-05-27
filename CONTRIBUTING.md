# Contributing to ForgotPasswordBundle

First of all, thank you for contributing, you're awesome!

To have your code integrated in the ForgotPasswordBundle project, there is some rules to follow, but don't panic,
it's easy!

## Reporting bugs

If you happen to find a bug, we kindly request you to report it using GitHub by following these 3 points:

  * Check if the bug is not already reported
  * A clear title to resume the issue
  * A description of the workflow needed to reproduce the bug

> _NOTE:_ Don't hesitate giving as much information as you can (OS, PHP version, extensions...)

## Pull Requests

### Matching coding standards

The PostmanGeneratorBundle project follows [Symfony coding standards](https://symfony.com/doc/current/contributing/code/standards.html).
But don't worry, you can fix CS issues automatically using the [PHP CS Fixer](http://cs.sensiolabs.org/) tool

```bash
php-cs-fixer.phar fix
```

And then, add fixed file to your commit before push. Be sure to add only **your modified files**. If another files are
fixed by cs tools, just revert it before commit.

### Sending a Pull Request

When you send a PR, just make sure that:

* You add valid test cases (Behat and PHPUnit)
* Tests are green
* You add some documentation (PHPDoc & user doc: README, custom documentation file)
* You make the PR on the same branch you based your changes on. If you see commits that you did not make in your PR,
you're doing it wrong
* Also don't forget to add a comment when you update a PR with a ping to the maintainer (`@vincentchalamon`),
so we will get a notification
* [Squash your commits](#squash-your-commits) into one commit

All Pull Requests must include [this header](.github/PULL_REQUEST_TEMPLATE.md).

## Squash your commits

If you have 3 commits. So start with:

```bash
git rebase -i HEAD~3
```

An editor will be opened with your 3 commits, all prefixed by `pick`.

Replace all `pick` prefixes by `fixup` (or `f`) **except the first commit** of the list.

Save and quit the editor.

After that, all your commits where squashed into the first one and the commit message of the first commit.

If you would like to rename your commit message type:

```bash
git commit --amend
```

Now force push to update your PR:

```bash
git push --force
```

# License and copyright attribution

When you open a Pull Request to the PostmanGeneratorBundle project, you agree to license your code under the
[MIT license](LICENSE) and to transfer the copyright on the submitted code to Vincent Chalamon.

Be sure to you have the right to do that (if you are a professional, ask your company)!

If you include code from another project, please mention it in the Pull Request description and credit the original
author.