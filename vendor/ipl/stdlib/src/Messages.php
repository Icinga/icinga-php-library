<?php

namespace ipl\Stdlib;

/**
 * Store, retrieve, and manage a list of string messages
 */
trait Messages
{
    /** @var string[] */
    protected array $messages = [];

    /**
     * Get whether there are any messages
     *
     * @return bool
     */
    public function hasMessages(): bool
    {
        return ! empty($this->messages);
    }

    /**
     * Get all messages
     *
     * @return string[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Set the given messages overriding existing ones
     *
     * @param string[] $messages
     *
     * @return $this
     */
    public function setMessages(array $messages): static
    {
        $this->clearMessages();

        foreach ($messages as $message) {
            $this->addMessage($message);
        }

        return $this;
    }

    /**
     * Add a single message
     *
     * @param string $message
     * @param mixed ...$args Optional args for sprintf-style messages
     *
     * @return $this
     */
    public function addMessage(string $message, mixed ...$args): static
    {
        if (empty($args)) {
            $this->messages[] = $message;
        } else {
            $this->messages[] = vsprintf($message, $args);
        }

        return $this;
    }

    /**
     * Add the given messages
     *
     * @param string[] $messages
     *
     * @return $this
     */
    public function addMessages(array $messages): static
    {
        $this->messages = array_merge($this->messages, $messages);

        return $this;
    }

    /**
     * Drop any existing message
     *
     * @return $this
     */
    public function clearMessages(): static
    {
        $this->messages = [];

        return $this;
    }
}
