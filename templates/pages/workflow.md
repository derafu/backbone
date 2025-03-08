# Typical Workflow

{.w-75 .mx-auto}
![Derafu Backbone Workflow](/img/derafu-backbone-workflow.svg)

1. A client interacts with a public method on a Worker.
2. The Worker can:
   - Execute simple logic internally.
   - Delegate to a Job for a specific task.
   - Delegate to a Handler for a complex process.

3. If a Handler is used:
   - The Handler coordinates the workflow.
   - May execute multiple Jobs in sequence.
   - May select different Strategies based on context.
   - Manages errors and transactions.

4. Strategies allow for varying the implementation of specific steps without changing the Handler's logic.
