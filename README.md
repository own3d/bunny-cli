# Bunny CLI - Replicate and store your files to the edge!

## What is Bunny CLI?

Bunny CLI is a tool for the console to upload frontend frameworks such as Angular, Vue.js, React, or more recently, Blazor quickly to the Edge Store on Bunny CDN.

With Bunny CDN's Storage Edge, your web applications benefit from replicated storage zones, a global content delivery network that hosts files in 5 different regions worldwide and accelerates everything through a worldwide content delivery network with over 54+ PoPs.

## How do I use Bunny CLI?

To install Bunny CLI, you need to be using Composer. For more details about Composer, see the [Composer documentation](https://getcomposer.org/doc/).

```bash
composer global require own3d/bunny-cli
```

Bunny CLI currently only comes with a `deploy` command. With this command, you can easily synconizise your `dist` folder with your edge storage.

> All files in the edge storage that are **not** in your local `dist` directory will be deleted.

```plain
➜ $ bunny deploy  
- Hashing files...  
✔ Finished hashing 16360 files  
- CDN diffing files...  
✔ CDN requesting 10875 files  
- Synchronizing 10875 files  
 10875/10875 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%  
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
  with:
    args: deploy --dir=dist
```

## Frequently Asked Questions

### Q: Is this a zero-downtime deployment?

A: Depends. Only when the sync of the files is complete, the pull zone cache is cleared. Therefore if the CDN cache is not present because the cache has expired or miss, then an unanticipated event may occur.

We hope that together with Bunny CDN, we can solve this problem.

### Q: Is this an official tool of Bunny CDN?

A: No. Bunny CLI is a community-driven tool and is not affiliated with Bunny CDN.

## License

Bunny CLI is an open-source software licensed under the Apache 2.0 license.
