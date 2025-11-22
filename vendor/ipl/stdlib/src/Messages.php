<?php

namespace ipl\Stdlib;

trait Messages
{
    /** @var array */
    protected $messages = [];

    /**
     * Get whether there are any messages
     *
     * @return bool
     */
    public function hasMessages()
    {
        return ! empty($this->messages);
    }

    /**
     * Get all messages
     *
     * @return array
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
    public function setMessages(array $messages)
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
     * @param mixed  ...$args Optional args for sprintf-style messages
     *
     * @return $this
     */
    public function addMessage($message, ...$args)
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
     * @param array $messages
     *
     * @return $this
     */
    public function addMessages(array $messages)
    {
        $this->messages = array_merge($this->messages, $messages);

        return $this;
    }

    /**
     * Drop any existing message
     *
     * @return $this
     */
    public function clearMessages()
    {
        $this->messages = [];

        return $this;
    }
}
