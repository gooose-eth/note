<article class="error">
  <div class="error__wrap">
    <h1 class="error__title">{status}</h1>
    <p class="error__message">{message}</p>
  </div>
</article>

<script>
export let status
export let message
</script>

<style lang="scss">
@use '../assets/scss/mixins';
.error {
  --color-step-1: #EB6635;
  --color-step-2: #B31E36;
  --color-step-3: #3422FF;
  display: grid;
  place-content: center;
  width: 100%;
  height: 100%;
  user-select: none;
  padding: 36px 0;
  box-sizing: border-box;
  &__wrap {
    text-align: center;
  }
  &__title {
    margin: 24px 0 0;
    font-family: var(--font-eng);
    font-size: 52px;
    color: var(--color-edge);
    line-height: 1;
  }
  &__message {
    margin: 2px 0 0;
    font-family: var(--font-eng);
    font-size: 14px;
    color: var(--color-text-blur);
    line-height: 1.15;
    font-weight: 200;
    letter-spacing: -.5px;
  }
  @include mixins.dark-mode() {
    --color-step-1: #f7cf1e;
    --color-step-2: var(--color-key);
    --color-step-3: #09abb5;
  }
  @include mixins.responsive(tablet) {
    &__title {
      margin-top: 36px;
      font-size: 72px;
    }
    &__message {
      margin-top: 4px;
      font-size: 20px;
    }
  }
}
</style>
