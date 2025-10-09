<?php

namespace App\Notifications;

use App\Models\Movie;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMovieAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The movie instance.
     */
    protected Movie $movie;

    /**
     * The genres that match user's preferences.
     */
    protected array $matchingGenres;

    /**
     * Create a new notification instance.
     */
    public function __construct(Movie $movie, array $matchingGenres = [])
    {
        $this->movie = $movie;
        $this->matchingGenres = $matchingGenres;
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $genreText = !empty($this->matchingGenres) 
            ? ' in ' . implode(', ', $this->matchingGenres) 
            : '';

        return (new MailMessage)
            ->subject("🎬 New Movie Added: {$this->movie->title}")
            ->greeting("Hello {$notifiable->username}!")
            ->line("A new movie has been added that matches your interests{$genreText}.")
            ->line("**{$this->movie->title}** ({$this->movie->year})")
            ->line($this->movie->overview ?? $this->movie->description ?? 'No description available.')
            ->line("**Rating:** {$this->movie->rating}/10")
            ->line("**Duration:** {$this->movie->runtime} minutes")
            ->action('Watch Now', url("/movies/{$this->movie->slug}"))
            ->line('Enjoy watching!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $genreText = !empty($this->matchingGenres) 
            ? ' in ' . implode(', ', $this->matchingGenres) 
            : '';

        return [
            'type' => 'new_movie_added',
            'icon' => 'film',
            'color' => 'blue',
            'title' => 'New Movie Added',
            'message' => "New movie{$genreText}: {$this->movie->title}",
            'movie_id' => $this->movie->id,
            'movie_title' => $this->movie->title,
            'movie_slug' => $this->movie->slug,
            'movie_year' => $this->movie->year,
            'movie_rating' => $this->movie->rating,
            'movie_poster' => $this->movie->poster_url ?? $this->movie->poster_path,
            'genres' => $this->matchingGenres,
            'action_url' => url("/movies/{$this->movie->slug}"),
            'action_text' => 'Watch Now',
        ];
    }
}
