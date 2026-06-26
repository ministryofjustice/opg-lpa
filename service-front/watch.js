import watcher from "@parcel/watcher";
import path from "path";
import { exec } from "node:child_process";

// Subscribe to events
await watcher.subscribe(
  path.join(process.cwd(), "assets/js"),
  (err, events) => {
    const build = exec("node build.js");
    build.stdout.pipe(process.stdout);
  },
  {
    ignore: ["**/lpa.templates.js"],
  },
);

await watcher.subscribe(
  path.join(process.cwd(), "assets/sass"),
  (err, events) => {
    const build = exec("./build-css.sh");
    build.stdout.pipe(process.stdout);
  },
);
