# Bunny CLI - Replicate and store your files to the edge!

> Please use Bunny CLI for development purposes only. We have not yet released a stable release.

## What is Bunny CLI?

Bunny CLI is a tool for the console to upload frontend frameworks such as Angular, Vue.js, React, or more recently, Blazor quickly to the Edge Store on Bunny CDN.

With Bunny CDN's Storage Edge, your web applications benefit from replicated storage zones, a global content delivery network that hosts files in 5 different regions worldwide and accelerates everything through a worldwide content delivery network with over 54+ PoPs.

## How do I use Bunny CLI?

To install Bunny CLI, you need to be using Composer. For more details about Composer, see the [Composer documentation](https://getcomposer.org/doc/).

```bash
composer global require own3d/bunny-cli
```

If you want to update the Bunny CLI, just execute the following command:

```bash
composer global update own3d/bunny-cli
```

After you install Bunny CLI, the next step is typically run the `bunny init` command to perform initial setup tasks. You can also run `bunny init` at a later time to change your settings or create a new configuration.

### Run Bunny Init

To initialize Bunny CLI:

1. Run `bunny init`:

```bash
bunny init
```

2. Configure your API credentials.

In order for the Bunny CLI to work properly you need to store your Bunny CDN API token. You can find your API token in your [Account Settings](https://panel.bunny.net/account).

3. Choose a current Storage Zone if prompted.

If you only have access to one storage zone, including the default pull zone, `bunny init` selects it for you.

When `bunny init` finishes, it saves the environment variables in your .env file.

You can view these environment variables at any other time using the `bunny env:list` command.

### Deploy your first project

With the `bunny deploy` command, you can easily synchronize your `dist` folder with your edge storage.

> **IMPORTANT**: All files in the edge storage that are **not** in your local `dist` directory will be deleted.

```plain
➜ $ bunny deploy  
- Hashing files...  
✔ Finished hashing 16360 files  
- CDN diffing files...  
✔ CDN requesting 10875 files  
- Synchronizing 10875 files  
 10875/10875 [============================] 100%  
✔ Finished synchronizing 10875 files  
- Waiting for deploy to go live...  
✔ Deployment is live! (322.96s)

Website URL: https://bunny-cli.b-cdn.net/
```

## How do I integrate Bunny CLI into my GitHub Actions workflow?

We offer you a [GitHub Action for Bunny CLI](https://github.com/marketplace/actions/bunny-cli) for free. You can easily upload your distributable files to your edge storage during your deployment process with this action. Just put your storage password (`BUNNY_STORAGE_PASSWORD`) and your API key (`BUNNY_API_ACCESS_KEY`) in the secrets of your GitHub repository and adjust your workflow as follows.

```
- name: Deploy to Edge Storage
  uses: own3d/bunny-action@main
  env:
    BUNNY_API_ACCESS_KEY: ${{ secrets.BUNNY_API_ACCESS_KEY }}
    BUNNY_STORAGE_USERNAME: bunny-cli
    BUNNY_STORAGE_PASSWORD: ${{ secrets.BUNNY_STORAGE_PASSWORD }}
    BUNNY_PULL_ZONE_ID: 466588
    BUNNY_EDGE_PATH: some_target_edge_dir
  with:
    args: deploy --dir=dist
```

## Environment Variables

You can customize your environment file at any time. The following commands are available for this purpose:

| Command                 | Description                                            |
|-------------------------|--------------------------------------------------------|
| `env:list`              | List all current environment variables.                |
| `env:set {key} {value}` | Set and save an environment variable in the .env file. |
| `env:backup {file}`     | Backup .env file into a given file.                    |
| `env:restore {file}`    | Restore .env file from a given file.                   |

## Secure your `.well-known/bunny-cli.lock` file

Bunny CLI generates a lock file, which is located at `.well-known/bunny-cli.lock` by default. This file locks the files of your project to a known state. To prevent this from being publicly accessible it is recommended to create a new edge rule in your pull zone. You can use the following example as a template:

Action: `Block Request`  
Condition Matching: `Match Any`  
Condition: If `Request URL` `Match Any` `*/.well-known/bunny-cli.lock`

Now the file should no longer be accessible. It can take a few minutes until your Edge Rule is active.

## Frequently Asked Questions

### Q: Is this a zero-downtime deployment?

A: Depends. Only when the sync of the files is complete, the pull zone cache is cleared. Therefore if the CDN cache is not present because the cache has expired or miss, then an unanticipated event may occur.

We hope that together with Bunny CDN, we can solve this problem.

### Q: Is this an official tool of Bunny CDN?

A: No. Bunny CLI is a community-driven tool and is not affiliated with Bunny CDN.

## License

Bunny CLI is an open-source software licensed under the Apache 2.0 license.
